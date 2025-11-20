<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Google Analytics Data API Service
 *
 * SETUP INSTRUCTIONS:
 *
 * 1. Create a Google Cloud Project:
 *    - Go to https://console.cloud.google.com/
 *    - Create a new project or select an existing one
 *
 * 2. Enable the Google Analytics Data API:
 *    - Go to APIs & Services > Library
 *    - Search for "Google Analytics Data API"
 *    - Click "Enable"
 *
 * 3. Create a Service Account:
 *    - Go to APIs & Services > Credentials
 *    - Click "Create Credentials" > "Service Account"
 *    - Give it a name and description
 *    - Skip granting access (click "Continue" twice)
 *    - Click on the created service account
 *    - Go to "Keys" tab > "Add Key" > "Create new key" > "JSON"
 *    - Download the JSON file
 *
 * 4. Add Service Account to Google Analytics:
 *    - Go to Google Analytics (analytics.google.com)
 *    - Admin > Property > Property Access Management
 *    - Add the service account email (from JSON file) as a "Viewer"
 *
 * 5. Configure Environment Variables:
 *    - Copy the JSON file to storage/app/google-credentials.json (or another secure location)
 *    - Add to .env:
 *      GOOGLE_APPLICATION_CREDENTIALS=/path/to/storage/app/google-credentials.json
 *      GOOGLE_ANALYTICS_PROPERTY_ID=123456789
 *
 * 6. Install the Google Analytics Data API client:
 *    composer require google/analytics-data
 *
 * Once configured, this service will provide real analytics data.
 */
class GoogleAnalyticsService
{
    private ?string $propertyId;

    private bool $isConfigured = false;

    public function __construct()
    {
        $this->propertyId = config('services.google.analytics_property_id');
        $this->isConfigured = $this->checkConfiguration();
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    private function checkConfiguration(): bool
    {
        return ! empty($this->propertyId)
            && ! empty(config('services.google.credentials_path'))
            && file_exists(config('services.google.credentials_path'));
    }

    /**
     * Get analytics overview data
     *
     * @return array{page_views: int, sessions: int, users: int, avg_session_duration: string, bounce_rate: string}
     */
    public function getOverview(int $days = 30): array
    {
        if (! $this->isConfigured) {
            return $this->getPlaceholderData();
        }

        $cacheKey = "analytics_overview_{$days}";

        return Cache::remember($cacheKey, now()->addHours(1), function () {
            // When Google Analytics Data API is configured, replace this with actual API calls:
            //
            // use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
            // use Google\Analytics\Data\V1beta\DateRange;
            // use Google\Analytics\Data\V1beta\Metric;
            //
            // $client = new BetaAnalyticsDataClient([
            //     'credentials' => config('services.google.credentials_path'),
            // ]);
            //
            // $response = $client->runReport([
            //     'property' => "properties/{$this->propertyId}",
            //     'dateRanges' => [new DateRange(['start_date' => "{$days}daysAgo", 'end_date' => 'today'])],
            //     'metrics' => [
            //         new Metric(['name' => 'screenPageViews']),
            //         new Metric(['name' => 'sessions']),
            //         new Metric(['name' => 'totalUsers']),
            //         new Metric(['name' => 'averageSessionDuration']),
            //         new Metric(['name' => 'bounceRate']),
            //     ],
            // ]);

            return $this->getPlaceholderData();
        });
    }

    /**
     * Get top pages by views
     *
     * @return array<array{page: string, views: int}>
     */
    public function getTopPages(int $limit = 10, int $days = 30): array
    {
        if (! $this->isConfigured) {
            return $this->getPlaceholderTopPages();
        }

        $cacheKey = "analytics_top_pages_{$limit}_{$days}";

        return Cache::remember($cacheKey, now()->addHours(1), function () {
            // When configured, use Dimensions with Metrics:
            //
            // $response = $client->runReport([
            //     'property' => "properties/{$this->propertyId}",
            //     'dateRanges' => [new DateRange(['start_date' => '30daysAgo', 'end_date' => 'today'])],
            //     'dimensions' => [new Dimension(['name' => 'pagePath'])],
            //     'metrics' => [new Metric(['name' => 'screenPageViews'])],
            //     'orderBys' => [...],
            //     'limit' => $limit,
            // ]);

            return $this->getPlaceholderTopPages();
        });
    }

    private function getPlaceholderData(): array
    {
        return [
            'page_views' => 0,
            'sessions' => 0,
            'users' => 0,
            'avg_session_duration' => '0:00',
            'bounce_rate' => '0%',
            'is_placeholder' => true,
        ];
    }

    private function getPlaceholderTopPages(): array
    {
        return [
            ['page' => '/documentation', 'views' => 0],
            ['page' => '/', 'views' => 0],
        ];
    }

    public function clearCache(): void
    {
        Cache::forget('analytics_overview_7');
        Cache::forget('analytics_overview_30');
        Cache::forget('analytics_top_pages_10_30');
    }
}
