<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NpmService
{
    private string $registryUrl = 'https://registry.npmjs.org';

    private string $downloadsUrl = 'https://api.npmjs.org/downloads';

    /**
     * Get package statistics from NPM
     *
     * @param  string  $packageName  The package name (e.g., '@artisanpack-ui/livewire-drag-and-drop')
     * @return array{downloads: array, name: string, description: string|null}|null
     */
    public function getPackageStats(string $packageName): ?array
    {
        $cacheKey = "npm_stats_{$packageName}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($packageName) {
            try {
                // Get package info
                $encodedName = str_replace('/', '%2F', $packageName);
                $infoResponse = Http::get("{$this->registryUrl}/{$encodedName}");

                if (! $infoResponse->successful()) {
                    return null;
                }

                $info = $infoResponse->json();

                // Get download counts
                $downloads = $this->getDownloadCounts($packageName);

                return [
                    'name' => $info['name'] ?? $packageName,
                    'description' => $info['description'] ?? null,
                    'downloads' => $downloads,
                ];
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Get download counts for a package
     *
     * @return array{total: int, monthly: int, weekly: int}
     */
    private function getDownloadCounts(string $packageName): array
    {
        $encodedName = str_replace('/', '%2F', $packageName);

        // Get last month downloads
        $monthlyResponse = Http::get("{$this->downloadsUrl}/point/last-month/{$encodedName}");
        $monthly = $monthlyResponse->successful() ? ($monthlyResponse->json('downloads') ?? 0) : 0;

        // Get last week downloads
        $weeklyResponse = Http::get("{$this->downloadsUrl}/point/last-week/{$encodedName}");
        $weekly = $weeklyResponse->successful() ? ($weeklyResponse->json('downloads') ?? 0) : 0;

        // NPM doesn't provide total downloads easily, so we'll estimate based on monthly
        // Or you could fetch historical data if needed
        $total = $monthly * 12; // Rough estimate

        return [
            'total' => $total,
            'monthly' => $monthly,
            'weekly' => $weekly,
        ];
    }

    /**
     * Get aggregated stats for multiple packages
     *
     * @param  array<string>  $packageNames  Array of package names
     * @return array{total_downloads: int, monthly_downloads: int, weekly_downloads: int, packages: array}
     */
    public function getAggregatedStats(array $packageNames): array
    {
        $stats = [
            'total_downloads' => 0,
            'monthly_downloads' => 0,
            'weekly_downloads' => 0,
            'packages' => [],
        ];

        foreach ($packageNames as $packageName) {
            $packageStats = $this->getPackageStats($packageName);

            if ($packageStats) {
                $stats['total_downloads'] += $packageStats['downloads']['total'];
                $stats['monthly_downloads'] += $packageStats['downloads']['monthly'];
                $stats['weekly_downloads'] += $packageStats['downloads']['weekly'];
                $stats['packages'][$packageName] = $packageStats;
            }
        }

        return $stats;
    }

    /**
     * Clear cached stats for a package
     */
    public function clearCache(string $packageName): void
    {
        Cache::forget("npm_stats_{$packageName}");
    }
}
