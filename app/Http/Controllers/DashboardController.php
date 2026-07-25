<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Analytics\AnalyticsSource;
use App\Analytics\DashboardDataSourceManager;
use App\Services\GoogleSearchConsoleService;
use App\Services\NpmService;
use App\Services\PackagistService;
use ArtisanPackUI\Analytics\Data\DateRange;
use ArtisanPackUI\Analytics\Services\AnalyticsQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Packages\Package;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        PackagistService $packagist,
        NpmService $npm,
        AnalyticsQuery $analytics,
        GoogleSearchConsoleService $searchConsole,
    ): Response {
        // Registry download counts, first-party/GA traffic, and Search
        // Console data are gated behind `view-analytics` (admin-only)
        // everywhere else in the app — the dashboard route itself only
        // requires `auth+verified`, so mirror that gate here rather
        // than surfacing the data to verified editors.
        $canViewAnalytics = $request->user()?->can('view-analytics') ?? false;

        $packages = Package::withCount('documentation')->orderBy('name')->get();

        $packagistStats = $canViewAnalytics
            ? $this->packagistStats($packagist, $packages)
            : $this->emptyPackagistStats();

        $npmStats = $canViewAnalytics
            ? $this->npmStats($npm, $packages)
            : $this->emptyNpmStats();

        $needsReimport = $packages->filter(fn (Package $p) => $p->needsDocumentationReimport())->count();
        $neverImported = $packages->whereNull('docs_imported_at')->count();
        $upToDate = $packages->count() - $needsReimport;

        return Inertia::render('Dashboard/Index', [
            'can_view_analytics' => $canViewAnalytics,
            'totals' => [
                'packages' => $packages->count(),
                'documentation' => (int) $packages->sum('documentation_count'),
                'downloads' => $packagistStats['total'] + $npmStats['total'],
                'monthly_downloads' => $packagistStats['monthly'] + $npmStats['monthly'],
                'needs_reimport' => $needsReimport,
                'up_to_date' => $upToDate,
                'never_imported' => $neverImported,
                'packagist_count' => $packages->filter(fn (Package $p) => $p->isPackagist())->count(),
                'npm_count' => $packages->filter(fn (Package $p) => $p->isNpm())->count(),
                'unlinked_count' => $packages->filter(fn (Package $p) => ! $p->isPackagist() && ! $p->isNpm())->count(),
                'stars' => $packagistStats['stars'],
            ],
            'packages' => $packages->map(fn (Package $p) => $this->transformPackage($p))->values(),
            'packagist' => $packagistStats,
            'npm' => $npmStats,
            'analytics' => $canViewAnalytics ? $this->analyticsPayload($analytics) : null,
            'search_console' => $canViewAnalytics ? $this->searchConsolePayload($searchConsole) : null,
            'registry_breakdown' => $this->registryBreakdown($packages),
            'status_breakdown' => $this->statusBreakdown($upToDate, $needsReimport, $neverImported),
            'top_documented' => $this->topDocumented($packages),
            'recent_imports' => $this->recentImports($packages),
            'docs_distribution' => $packages
                ->sortByDesc('documentation_count')
                ->pluck('documentation_count')
                ->map(fn ($v) => (int) $v)
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array{is_configured: bool, monthly: int, daily: int, total: int, stars: int, top_packages: array<int, array{name: string, monthly: int, total: int}>}
     */
    private function emptyPackagistStats(): array
    {
        return [
            'is_configured' => false,
            'monthly' => 0,
            'daily' => 0,
            'total' => 0,
            'stars' => 0,
            'top_packages' => [],
        ];
    }

    /**
     * @return array{is_configured: bool, monthly: int, weekly: int, total: int, top_packages: array<int, array{name: string, monthly: int, total: int}>}
     */
    private function emptyNpmStats(): array
    {
        return [
            'is_configured' => false,
            'monthly' => 0,
            'weekly' => 0,
            'total' => 0,
            'top_packages' => [],
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string, icon: ?string, version: ?string, docs_count: int, docs_imported_at: ?string, last_import_human: ?string, registry: ?string, registry_name: ?string, status: string}
     */
    private function transformPackage(Package $package): array
    {
        $status = 'up_to_date';
        if ($package->docs_imported_at === null) {
            $status = 'never';
        } elseif ($package->needsDocumentationReimport()) {
            $status = 'needs_update';
        }

        return [
            'id' => $package->id,
            'name' => $package->name,
            'slug' => $package->slug,
            'icon' => $package->icon,
            'version' => $package->version,
            'docs_count' => (int) $package->documentation_count,
            'docs_imported_at' => $package->docs_imported_at?->toIso8601String(),
            'last_import_human' => $package->docs_imported_at?->diffForHumans(),
            'registry' => $package->package_registry,
            'registry_name' => $package->getRegistryPackageName(),
            'status' => $status,
        ];
    }

    /**
     * @param  Collection<int, Package>  $packages
     * @return array{is_configured: bool, monthly: int, daily: int, total: int, stars: int, top_packages: array<int, array{name: string, monthly: int, total: int}>}
     */
    private function packagistStats(PackagistService $service, Collection $packages): array
    {
        $names = $packages
            ->filter(fn (Package $p) => $p->isPackagist())
            ->map(fn (Package $p) => $p->getRegistryPackageName())
            ->filter()
            ->values()
            ->all();

        if ($names === []) {
            return [
                'is_configured' => false,
                'monthly' => 0,
                'daily' => 0,
                'total' => 0,
                'stars' => 0,
                'top_packages' => [],
            ];
        }

        $stats = $service->getAggregatedStats($names);

        $top = collect($stats['packages'] ?? [])
            ->map(fn (array $data, string $name) => [
                'name' => $name,
                'monthly' => (int) ($data['downloads']['monthly'] ?? 0),
                'total' => (int) ($data['downloads']['total'] ?? 0),
            ])
            ->sortByDesc('monthly')
            ->take(5)
            ->values()
            ->all();

        return [
            'is_configured' => true,
            'monthly' => (int) ($stats['monthly_downloads'] ?? 0),
            'daily' => (int) ($stats['daily_downloads'] ?? 0),
            'total' => (int) ($stats['total_downloads'] ?? 0),
            'stars' => (int) ($stats['total_favers'] ?? 0),
            'top_packages' => $top,
        ];
    }

    /**
     * @param  Collection<int, Package>  $packages
     * @return array{is_configured: bool, monthly: int, weekly: int, total: int, top_packages: array<int, array{name: string, monthly: int, total: int}>}
     */
    private function npmStats(NpmService $service, Collection $packages): array
    {
        $names = $packages
            ->filter(fn (Package $p) => $p->isNpm())
            ->map(fn (Package $p) => $p->getRegistryPackageName())
            ->filter()
            ->values()
            ->all();

        if ($names === []) {
            return [
                'is_configured' => false,
                'monthly' => 0,
                'weekly' => 0,
                'total' => 0,
                'top_packages' => [],
            ];
        }

        $stats = $service->getAggregatedStats($names);

        $top = collect($stats['packages'] ?? [])
            ->map(fn (array $data, string $name) => [
                'name' => $name,
                'monthly' => (int) ($data['downloads']['monthly'] ?? 0),
                'total' => (int) ($data['downloads']['total'] ?? 0),
            ])
            ->sortByDesc('monthly')
            ->take(5)
            ->values()
            ->all();

        return [
            'is_configured' => true,
            'monthly' => (int) ($stats['monthly_downloads'] ?? 0),
            'weekly' => (int) ($stats['weekly_downloads'] ?? 0),
            'total' => (int) ($stats['total_downloads'] ?? 0),
            'top_packages' => $top,
        ];
    }

    /**
     * Build the analytics payload from the shared AnalyticsQuery service
     * (which the container swaps for DashboardDataSourceManager, so the
     * numbers reflect whichever source — first-party analytics or
     * Google Analytics — is active in Settings).
     */
    private function analyticsPayload(AnalyticsQuery $analytics): array
    {
        $range = DateRange::last30Days();
        $stats = $analytics->getStats($range, withCompare: false);
        $topPages = $analytics->getTopPages($range, 5);

        $source = $analytics instanceof DashboardDataSourceManager
            ? $analytics->activeSource()
            : AnalyticsSource::Local;

        $avgSeconds = (int) round((float) ($stats['avg_session_duration'] ?? 0));

        return [
            'is_configured' => true,
            'source' => $source->value,
            'source_label' => $source === AnalyticsSource::Google
                ? 'Google Analytics'
                : 'First-party analytics',
            'page_views' => (int) ($stats['pageviews'] ?? 0),
            'sessions' => (int) ($stats['sessions'] ?? 0),
            'users' => (int) ($stats['visitors'] ?? 0),
            'bounce_rate' => number_format((float) ($stats['bounce_rate'] ?? 0), 1).'%',
            'avg_session_duration' => $this->formatDuration($avgSeconds),
            'top_pages' => $topPages
                ->take(5)
                ->map(fn (array $row) => [
                    'page' => (string) ($row['path'] ?? '/'),
                    'views' => (int) ($row['views'] ?? 0),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Search Console has no fallback — return null when it isn't
     * configured so the frontend can drop the panel entirely instead
     * of rendering a "not configured" placeholder.
     */
    private function searchConsolePayload(GoogleSearchConsoleService $service): ?array
    {
        if (! $service->isConfigured()) {
            return null;
        }

        $overview = $service->getOverview(28);

        return [
            'clicks' => (int) ($overview['clicks'] ?? 0),
            'impressions' => (int) ($overview['impressions'] ?? 0),
            'ctr' => $overview['ctr'] ?? '0%',
            'position' => (float) ($overview['position'] ?? 0),
            'top_queries' => $service->getTopQueries(5, 28),
        ];
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intdiv($seconds, 60);
        $remainder = $seconds % 60;

        return "{$minutes}m {$remainder}s";
    }

    /**
     * @param  Collection<int, Package>  $packages
     * @return array<int, array{label: string, value: int}>
     */
    private function registryBreakdown(Collection $packages): array
    {
        return [
            ['label' => 'Packagist', 'value' => $packages->filter(fn (Package $p) => $p->isPackagist())->count()],
            ['label' => 'NPM', 'value' => $packages->filter(fn (Package $p) => $p->isNpm())->count()],
            ['label' => 'Unlinked', 'value' => $packages->filter(fn (Package $p) => ! $p->isPackagist() && ! $p->isNpm())->count()],
        ];
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function statusBreakdown(int $upToDate, int $needsReimport, int $neverImported): array
    {
        $needsUpdateOnly = max(0, $needsReimport - $neverImported);

        return [
            ['label' => 'Up to date', 'value' => $upToDate],
            ['label' => 'Needs update', 'value' => $needsUpdateOnly],
            ['label' => 'Never imported', 'value' => $neverImported],
        ];
    }

    /**
     * @param  Collection<int, Package>  $packages
     * @return array<int, array{name: string, docs_count: int, slug: string}>
     */
    private function topDocumented(Collection $packages): array
    {
        return $packages
            ->sortByDesc('documentation_count')
            ->take(6)
            ->map(fn (Package $p) => [
                'name' => $p->name,
                'slug' => $p->slug,
                'docs_count' => (int) $p->documentation_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Package>  $packages
     * @return array<int, array{name: string, slug: string, last_import_human: string, imported_at: string}>
     */
    private function recentImports(Collection $packages): array
    {
        return $packages
            ->filter(fn (Package $p) => $p->docs_imported_at !== null)
            ->sortByDesc('docs_imported_at')
            ->take(6)
            ->map(fn (Package $p) => [
                'name' => $p->name,
                'slug' => $p->slug,
                'imported_at' => $p->docs_imported_at->toIso8601String(),
                'last_import_human' => $p->docs_imported_at->diffForHumans(),
            ])
            ->values()
            ->all();
    }
}
