<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    /**
     * Show the API tokens settings page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

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
            ])
            ->all();

        return Inertia::render('Settings/ApiTokens', [
            'tokens' => $tokens,
            'availableAbilities' => TokenAbility::options(),
            'newToken' => $request->session()->get('new_api_token'),
            'status' => $request->session()->get('status'),
        ]);
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
}
