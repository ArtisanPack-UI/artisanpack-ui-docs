<?php

namespace Modules\Admin\Livewire;

use App\Services\GoogleAnalyticsService;
use App\Services\GoogleSearchConsoleService;
use App\Services\NpmService;
use App\Services\PackagistService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Package;

#[Layout('admin::layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        return view('admin::livewire.dashboard');
    }

    #[Computed]
    public function packages()
    {
        return Package::withCount('documentation')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function packagesNeedingReimport(): int
    {
        return $this->packages->filter(fn ($package) => $package->needsDocumentationReimport())->count();
    }

    #[Computed]
    public function totalDocumentation(): int
    {
        return $this->packages->sum('documentation_count');
    }

    #[Computed]
    public function packagistStats(): array
    {
        $packagistService = new PackagistService;

        $packageNames = $this->packages
            ->filter(fn ($package) => $package->isPackagist())
            ->map(fn ($package) => $package->getRegistryPackageName())
            ->filter()
            ->toArray();

        if (empty($packageNames)) {
            return [
                'total_downloads' => 0,
                'monthly_downloads' => 0,
                'daily_downloads' => 0,
                'total_favers' => 0,
                'packages' => [],
                'is_configured' => false,
            ];
        }

        $stats = $packagistService->getAggregatedStats($packageNames);
        $stats['is_configured'] = true;

        return $stats;
    }

    #[Computed]
    public function npmStats(): array
    {
        $npmService = new NpmService;

        $packageNames = $this->packages
            ->filter(fn ($package) => $package->isNpm())
            ->map(fn ($package) => $package->getRegistryPackageName())
            ->filter()
            ->toArray();

        if (empty($packageNames)) {
            return [
                'total_downloads' => 0,
                'monthly_downloads' => 0,
                'weekly_downloads' => 0,
                'packages' => [],
                'is_configured' => false,
            ];
        }

        $stats = $npmService->getAggregatedStats($packageNames);
        $stats['is_configured'] = true;

        return $stats;
    }

    #[Computed]
    public function totalDownloads(): int
    {
        return $this->packagistStats['total_downloads'] + $this->npmStats['total_downloads'];
    }

    #[Computed]
    public function analyticsData(): array
    {
        $service = new GoogleAnalyticsService;

        return [
            'overview' => $service->getOverview(30),
            'top_pages' => $service->getTopPages(5, 30),
            'is_configured' => $service->isConfigured(),
        ];
    }

    #[Computed]
    public function searchConsoleData(): array
    {
        $service = new GoogleSearchConsoleService;

        return [
            'overview' => $service->getOverview(28),
            'top_queries' => $service->getTopQueries(5, 28),
            'is_configured' => $service->isConfigured(),
        ];
    }

    public function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1).'M';
        }

        if ($number >= 1000) {
            return number_format($number / 1000, 1).'K';
        }

        return (string) $number;
    }
}
