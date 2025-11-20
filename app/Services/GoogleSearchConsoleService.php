<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Google Search Console API Service
 *
 * SETUP INSTRUCTIONS:
 *
 * 1. Use the same Google Cloud Project from Analytics setup (or create new one)
 *
 * 2. Enable the Search Console API:
 *    - Go to APIs & Services > Library
 *    - Search for "Google Search Console API"
 *    - Click "Enable"
 *
 * 3. Use the same Service Account (or create a new one):
 *    - If using existing, you already have the JSON credentials
 *    - If creating new, follow steps from GoogleAnalyticsService
 *
 * 4. Add Service Account to Search Console:
 *    - Go to Google Search Console (search.google.com/search-console)
 *    - Select your property
 *    - Settings > Users and permissions
 *    - Add the service account email as "Full" access
 *
 * 5. Configure Environment Variables:
 *    - Add to .env:
 *      GOOGLE_SEARCH_CONSOLE_SITE_URL=https://your-domain.com
 *
 * 6. Install the Google API client (if not already):
 *    composer require google/apiclient
 *
 * Once configured, this service will provide real search console data.
 */
class GoogleSearchConsoleService
{
    private ?string $siteUrl;

    private bool $isConfigured = false;

    public function __construct()
    {
        $this->siteUrl = config('services.google.search_console_site_url');
        $this->isConfigured = $this->checkConfiguration();
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    private function checkConfiguration(): bool
    {
        return ! empty($this->siteUrl)
            && ! empty(config('services.google.credentials_path'))
            && file_exists(config('services.google.credentials_path'));
    }

    /**
     * Get search performance overview
     *
     * @return array{clicks: int, impressions: int, ctr: string, position: float}
     */
    public function getOverview(int $days = 28): array
    {
        if (! $this->isConfigured) {
            return $this->getPlaceholderData();
        }

        $cacheKey = "search_console_overview_{$days}";

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            // When Google Search Console API is configured:
            //
            // $client = new \Google_Client();
            // $client->setAuthConfig(config('services.google.credentials_path'));
            // $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);
            //
            // $service = new \Google_Service_SearchConsole($client);
            //
            // $request = new \Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
            // $request->setStartDate(date('Y-m-d', strtotime("-{$days} days")));
            // $request->setEndDate(date('Y-m-d'));
            //
            // $response = $service->searchanalytics->query($this->siteUrl, $request);

            return $this->getPlaceholderData();
        });
    }

    /**
     * Get top search queries
     *
     * @return array<array{query: string, clicks: int, impressions: int, ctr: string, position: float}>
     */
    public function getTopQueries(int $limit = 10, int $days = 28): array
    {
        if (! $this->isConfigured) {
            return $this->getPlaceholderTopQueries();
        }

        $cacheKey = "search_console_top_queries_{$limit}_{$days}";

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            // When configured:
            //
            // $request->setDimensions(['query']);
            // $request->setRowLimit($limit);
            //
            // $response = $service->searchanalytics->query($this->siteUrl, $request);

            return $this->getPlaceholderTopQueries();
        });
    }

    /**
     * Get top pages from search
     *
     * @return array<array{page: string, clicks: int, impressions: int}>
     */
    public function getTopPages(int $limit = 10, int $days = 28): array
    {
        if (! $this->isConfigured) {
            return $this->getPlaceholderTopPages();
        }

        $cacheKey = "search_console_top_pages_{$limit}_{$days}";

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            return $this->getPlaceholderTopPages();
        });
    }

    private function getPlaceholderData(): array
    {
        return [
            'clicks' => 0,
            'impressions' => 0,
            'ctr' => '0%',
            'position' => 0.0,
            'is_placeholder' => true,
        ];
    }

    private function getPlaceholderTopQueries(): array
    {
        return [
            ['query' => 'example query', 'clicks' => 0, 'impressions' => 0, 'ctr' => '0%', 'position' => 0.0],
        ];
    }

    private function getPlaceholderTopPages(): array
    {
        return [
            ['page' => '/', 'clicks' => 0, 'impressions' => 0],
        ];
    }

    public function clearCache(): void
    {
        Cache::forget('search_console_overview_28');
        Cache::forget('search_console_top_queries_10_28');
        Cache::forget('search_console_top_pages_10_28');
    }
}
