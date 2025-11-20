<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PackagistService
{
    private string $baseUrl = 'https://packagist.org';

    /**
     * Get package statistics from Packagist
     *
     * @param  string  $packageName  The package name (e.g., 'vendor/package')
     * @return array{downloads: array, favers: int, dependents: int, github_stars: int|null, name: string, description: string|null}|null
     */
    public function getPackageStats(string $packageName): ?array
    {
        $cacheKey = "packagist_stats_{$packageName}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($packageName) {
            try {
                $response = Http::get("{$this->baseUrl}/packages/{$packageName}.json");

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json('package');

                return [
                    'name' => $data['name'] ?? $packageName,
                    'description' => $data['description'] ?? null,
                    'downloads' => [
                        'total' => $data['downloads']['total'] ?? 0,
                        'monthly' => $data['downloads']['monthly'] ?? 0,
                        'daily' => $data['downloads']['daily'] ?? 0,
                    ],
                    'favers' => $data['favers'] ?? 0,
                    'dependents' => $data['dependents'] ?? 0,
                    'github_stars' => $data['github_stars'] ?? null,
                ];
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Get aggregated stats for multiple packages
     *
     * @param  array<string>  $packageNames  Array of package names
     * @return array{total_downloads: int, monthly_downloads: int, daily_downloads: int, total_favers: int, packages: array}
     */
    public function getAggregatedStats(array $packageNames): array
    {
        $stats = [
            'total_downloads' => 0,
            'monthly_downloads' => 0,
            'daily_downloads' => 0,
            'total_favers' => 0,
            'packages' => [],
        ];

        foreach ($packageNames as $packageName) {
            $packageStats = $this->getPackageStats($packageName);

            if ($packageStats) {
                $stats['total_downloads'] += $packageStats['downloads']['total'];
                $stats['monthly_downloads'] += $packageStats['downloads']['monthly'];
                $stats['daily_downloads'] += $packageStats['downloads']['daily'];
                $stats['total_favers'] += $packageStats['favers'];
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
        Cache::forget("packagist_stats_{$packageName}");
    }
}
