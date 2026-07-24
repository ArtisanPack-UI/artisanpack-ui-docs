<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Http\Controllers\Controller;
use ArtisanPackUI\Privacy\Models\PrivacyPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Services\NavigationService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PolicyController extends Controller
{
    public function __construct(protected NavigationService $navigation) {}

    public function show(Request $request): Response
    {
        $locale = $this->resolveLocale($request);
        $policy = $this->resolveActivePolicy($request, $locale);

        if (! $policy instanceof PrivacyPolicy) {
            throw new NotFoundHttpException('No active privacy policy has been published.');
        }

        return $this->renderPolicy($policy, $locale);
    }

    public function showVersion(Request $request, string $version): Response
    {
        $locale = $this->resolveLocale($request);

        $policy = PrivacyPolicy::query()
            ->forVersion($version)
            ->forLocale($locale)
            ->latestFirst()
            ->first()
            ?? PrivacyPolicy::query()->forVersion($version)->latestFirst()->first();

        if (! $policy instanceof PrivacyPolicy) {
            throw new NotFoundHttpException("No privacy policy with version [{$version}] exists.");
        }

        return $this->renderPolicy($policy, $locale);
    }

    protected function renderPolicy(PrivacyPolicy $policy, string $locale): Response
    {
        $history = Cache::remember(
            "privacy.policy.history.{$policy->id}",
            3600,
            fn (): array => PrivacyPolicy::query()
                ->forRegulation($policy->regulation)
                ->forLocale($policy->locale)
                ->whereNotNull('published_at')
                ->latestFirst()
                ->select(['id', 'version', 'locale', 'regulation', 'published_at'])
                ->limit(50)
                ->get()
                ->map(fn (PrivacyPolicy $entry): array => [
                    'version' => $entry->version,
                    'locale' => $entry->locale,
                    'regulation' => $entry->regulation,
                    'published_at' => $entry->published_at?->toIso8601String(),
                    'url' => route('privacy.policy.show-version', ['version' => $entry->version]),
                ])
                ->all(),
        );

        return Inertia::render('Privacy/Policy', [
            'policy' => [
                'version' => $policy->version,
                'regulation' => $policy->regulation,
                'locale' => $policy->locale,
                'published_at' => $policy->published_at?->toIso8601String(),
                'is_active' => (bool) $policy->active,
                'html' => $policy->renderHtml(),
                'sections' => $policy->tableOfContents(),
            ],
            'history' => $history,
            'locale' => $locale,
            'policy_url' => route('privacy.policy.show'),
            'navigation' => [
                'pages' => $this->navigation->buildPages(),
                'packages' => $this->navigation->buildPackages(),
            ],
        ]);
    }

    protected function resolveActivePolicy(Request $request, string $locale): ?PrivacyPolicy
    {
        $regulation = $this->resolveRegulation($request);
        $base = PrivacyPolicy::query()->active()->latestFirst();

        if ($regulation !== null) {
            $specific = $this->firstForLocale(
                (clone $base)->forRegulation($regulation),
                $locale,
            );

            if ($specific instanceof PrivacyPolicy) {
                return $specific;
            }
        }

        return $this->firstForLocale(
            (clone $base)->forRegulation(null),
            $locale,
        );
    }

    protected function firstForLocale(Builder $query, string $locale): ?PrivacyPolicy
    {
        return (clone $query)->forLocale($locale)->first() ?? $query->first();
    }

    protected function resolveRegulation(Request $request): ?string
    {
        $override = $request->query('regulation');

        if (is_string($override) && $override !== '') {
            return strtolower($override);
        }

        return null;
    }

    protected function resolveLocale(Request $request): string
    {
        $query = $request->query('locale');

        if (is_string($query) && $query !== '') {
            return $query;
        }

        return (string) (config('app.locale') ?? 'en');
    }
}
