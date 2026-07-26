<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenController extends Controller
{
    /**
     * Age at which a PAT is flagged for rotation on `/settings/api-tokens`
     * (V2_REFACTOR_PLAN.md §5, §9.6 item #47).
     */
    public const ROTATION_AGE_DAYS = 90;

    /**
     * Show the API tokens settings page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $threshold = Carbon::now()->subDays(self::ROTATION_AGE_DAYS);

        $tokens = $user->tokens()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PersonalAccessToken $token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => array_values(array_filter(
                    (array) $token->abilities,
                    fn ($ability): bool => is_string($ability),
                )),
                'last_used_at' => optional($token->last_used_at)->toIso8601String(),
                'created_at' => optional($token->created_at)->toIso8601String(),
                'needs_rotation' => $token->created_at !== null
                    && $token->created_at->lessThan($threshold),
                'age_days' => $token->created_at !== null
                    ? (int) $token->created_at->diffInDays(Carbon::now())
                    : null,
            ])
            ->all();

        $response = Inertia::render('Settings/ApiTokens', [
            'tokens' => $tokens,
            'availableAbilities' => TokenAbility::options(),
            'newToken' => $request->session()->get('new_api_token'),
            'status' => $request->session()->get('status'),
            'rotationAgeDays' => self::ROTATION_AGE_DAYS,
        ])->toResponse($request);

        // Prevent bfcache / disk cache / intermediate proxies from retaining
        // the freshly issued plain-text token that is flashed into this view.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * Issue a new personal access token for the current user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(TokenAbility::values())],
        ]);

        $abilities = array_values(array_unique($validated['abilities']));

        $newToken = $request->user()->createToken($validated['name'], $abilities);

        return back()
            ->with('status', 'api-token-created')
            ->with('new_api_token', [
                'name' => $validated['name'],
                'plain_text' => $newToken->plainTextToken,
            ]);
    }

    /**
     * Revoke one of the current user's tokens.
     */
    public function destroy(Request $request, int $token): RedirectResponse
    {
        $deleted = $request->user()->tokens()->whereKey($token)->delete();

        if ($deleted === 0) {
            abort(404);
        }

        return back()->with('status', 'api-token-revoked');
    }

    /**
     * Rotate a stale token: revoke the old one and mint a new token with
     * the same name and abilities. The plain-text of the fresh token is
     * flashed to the session exactly like {@see store()} so the user can
     * copy it once (V2_REFACTOR_PLAN.md §5, §9.6 item #47).
     */
    public function rotate(Request $request, int $token): RedirectResponse
    {
        $user = $request->user();

        /** @var PersonalAccessToken|null $existing */
        $existing = $user->tokens()->whereKey($token)->first();

        if ($existing === null) {
            abort(404);
        }

        $abilities = array_values(array_filter(
            (array) $existing->abilities,
            fn ($ability): bool => is_string($ability) && TokenAbility::isAllowed($ability),
        ));

        if ($abilities === []) {
            // A rotated token must carry at least one ability, otherwise
            // it would be useless. Fall back to revoking so the stale
            // credential is still removed.
            $existing->delete();

            return back()->with('status', 'api-token-revoked');
        }

        $name = $existing->name;

        // Wrap the swap in a transaction so a failed createToken() rolls
        // back the delete — otherwise the user can be stranded with no
        // working token if the second write fails mid-flight.
        $newToken = DB::transaction(function () use ($user, $existing, $name, $abilities) {
            $existing->delete();

            return $user->createToken($name, $abilities);
        });

        return back()
            ->with('status', 'api-token-rotated')
            ->with('new_api_token', [
                'name' => $name,
                'plain_text' => $newToken->plainTextToken,
            ]);
    }
}
