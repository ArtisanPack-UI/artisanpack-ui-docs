<?php

declare(strict_types=1);

namespace App\Http;

use App\Analytics\AnalyticsSource;
use App\Analytics\AnalyticsSourceResolver;
use App\Models\User;
use App\Services\InertiaSeo;
use ArtisanPackUI\Privacy\Services\ReconsentService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Shared props available on every Inertia response.
     *
     * Keep the shape in sync with `SharedProps` in
     * `resources/js/types/inertia.d.ts`.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->value,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'seo' => fn () => app(InertiaSeo::class)->forData(
                title: config('seo.site.name', config('app.name')),
                description: config('seo.site.description') ?: null,
            ),
            'reconsent' => fn () => $this->reconsentPolicy($request),
            'analyticsSource' => fn () => $this->analyticsSourceProps($request),
        ];
    }

    /**
     * Shape used by the analytics sub-nav's source switcher. Only populated
     * for admins so a non-admin can neither see nor toggle the setting.
     *
     * @return array{active: string, options: array<string, string>, google_available: bool, update_url: string}|null
     */
    protected function analyticsSourceProps(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return null;
        }

        $resolver = app(AnalyticsSourceResolver::class);

        return [
            'active' => $resolver->active()->value,
            'options' => AnalyticsSource::options(),
            'google_available' => $resolver->isGoogleAvailable(),
            'update_url' => route('dashboard.analytics.source.update'),
        ];
    }

    /**
     * Shape used by the React `<PolicyReconsentBanner>` — `null` when the
     * authenticated user is already up to date, or when the visitor is a
     * guest and no policy is active.
     *
     * @return array{version: string, regulation: string|null, url: string}|null
     */
    protected function reconsentPolicy(Request $request): ?array
    {
        $reconsent = app(ReconsentService::class);

        $policy = $reconsent->currentPolicy();

        if ($policy === null) {
            return null;
        }

        $user = $request->user();

        if ($user !== null && $reconsent->isUpToDate($user)) {
            return null;
        }

        return [
            'version' => $policy->version,
            'regulation' => $policy->regulation,
            'url' => route('privacy.policy.show-version', ['version' => $policy->version]),
        ];
    }
}
