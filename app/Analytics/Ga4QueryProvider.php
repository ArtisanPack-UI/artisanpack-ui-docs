<?php

declare(strict_types=1);

namespace App\Analytics;

use ArtisanPackUI\Analytics\Contracts\AnalyticsQueryInterface;
use ArtisanPackUI\Analytics\Data\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\DateRange as GaDateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportRequest;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportResponse;
use ArtisanPackUI\Google\Exceptions\TokenRefreshException;
use ArtisanPackUI\Google\Tokens\TokenManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Collection;
use Throwable;

class Ga4QueryProvider implements AnalyticsQueryInterface
{
    private const M_PAGE_VIEWS = 'screenPageViews';

    private const M_USERS = 'totalUsers';

    private const M_SESSIONS = 'sessions';

    private const M_BOUNCE = 'bounceRate';

    private const M_AVG_DURATION = 'averageSessionDuration';

    private const M_EVENT_COUNT = 'eventCount';

    private const D_PAGE_PATH = 'pagePath';

    private const D_PAGE_TITLE = 'pageTitle';

    private const D_SOURCE = 'sessionSource';

    private const D_MEDIUM = 'sessionMedium';

    private const D_DEVICE = 'deviceCategory';

    private const D_BROWSER = 'browser';

    private const D_BROWSER_VERSION = 'browserVersion';

    private const D_COUNTRY = 'country';

    private const D_COUNTRY_ID = 'countryId';

    private const D_DATE = 'date';

    private const D_DATE_HOUR = 'dateHour';

    private const D_YEAR_WEEK = 'yearWeek';

    private const D_YEAR_MONTH = 'yearMonth';

    private const D_EVENT_NAME = 'eventName';

    public function __construct(
        private readonly Ga4DataClient $client,
        private readonly AnalyticsSourceResolver $resolver,
        private readonly ConfigRepository $config,
        private readonly HttpFactory $http,
        private readonly ?TokenManager $tokens = null,
        private readonly ?CacheRepository $cache = null,
    ) {}

    public function getPageViews(DateRange $range, array $filters = []): int
    {
        return (int) $this->totalMetric($range, self::M_PAGE_VIEWS);
    }

    public function getVisitors(DateRange $range, array $filters = []): int
    {
        return (int) $this->totalMetric($range, self::M_USERS);
    }

    public function getSessions(DateRange $range, array $filters = []): int
    {
        return (int) $this->totalMetric($range, self::M_SESSIONS);
    }

    public function getBounceRate(DateRange $range, array $filters = []): float
    {
        return round($this->totalMetric($range, self::M_BOUNCE) * 100, 2);
    }

    public function getAverageSessionDuration(DateRange $range, array $filters = []): int
    {
        return (int) round($this->totalMetric($range, self::M_AVG_DURATION));
    }

    public function getTopPages(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        $response = $this->runReport(
            $range,
            [self::M_PAGE_VIEWS, self::M_USERS],
            [self::D_PAGE_PATH, self::D_PAGE_TITLE],
            [['metric' => self::M_PAGE_VIEWS, 'desc' => true]],
            $this->clampLimit($limit),
        );

        if ($response === null) {
            return collect();
        }

        return collect($response->rows())->map(fn (array $row): array => [
            'path' => (string) ($row[self::D_PAGE_PATH] ?? ''),
            'title' => (string) ($row[self::D_PAGE_TITLE] ?? ''),
            'views' => (int) ($row[self::M_PAGE_VIEWS] ?? 0),
            'unique_views' => (int) ($row[self::M_USERS] ?? 0),
        ])->values();
    }

    public function getTrafficSources(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        $response = $this->runReport(
            $range,
            [self::M_SESSIONS, self::M_USERS],
            [self::D_SOURCE, self::D_MEDIUM],
            [['metric' => self::M_SESSIONS, 'desc' => true]],
            $this->clampLimit($limit),
        );

        if ($response === null) {
            return collect();
        }

        return collect($response->rows())->map(fn (array $row): array => [
            'source' => (string) ($row[self::D_SOURCE] ?? ''),
            'medium' => (string) ($row[self::D_MEDIUM] ?? ''),
            'sessions' => (int) ($row[self::M_SESSIONS] ?? 0),
            'visitors' => (int) ($row[self::M_USERS] ?? 0),
        ])->values();
    }

    public function getPageViewsOverTime(DateRange $range, string $granularity = 'day', array $filters = []): Collection
    {
        [$dateDimension, $dateFormatter] = $this->granularityConfig($granularity);

        $response = $this->runReport(
            $range,
            [self::M_PAGE_VIEWS, self::M_USERS],
            [$dateDimension],
            [['dimension' => $dateDimension, 'desc' => false]],
        );

        if ($response === null) {
            return collect();
        }

        return collect($response->rows())->map(fn (array $row): array => [
            'date' => $dateFormatter((string) ($row[$dateDimension] ?? '')),
            'pageviews' => (int) ($row[self::M_PAGE_VIEWS] ?? 0),
            'visitors' => (int) ($row[self::M_USERS] ?? 0),
        ])->values();
    }

    public function getDeviceBreakdown(DateRange $range, array $filters = []): Collection
    {
        $response = $this->runReport(
            $range,
            [self::M_SESSIONS],
            [self::D_DEVICE],
            [['metric' => self::M_SESSIONS, 'desc' => true]],
        );

        return $this->breakdownWithPercentage($response, self::D_DEVICE, self::M_SESSIONS, 'device_type');
    }

    public function getBrowserBreakdown(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        $response = $this->runReport(
            $range,
            [self::M_SESSIONS],
            [self::D_BROWSER, self::D_BROWSER_VERSION],
            [['metric' => self::M_SESSIONS, 'desc' => true]],
            $this->clampLimit($limit),
        );

        if ($response === null) {
            return collect();
        }

        $rows = $response->rows();
        $total = array_sum(array_map(fn (array $row): int => (int) ($row[self::M_SESSIONS] ?? 0), $rows));

        return collect($rows)->map(fn (array $row): array => [
            'browser' => (string) ($row[self::D_BROWSER] ?? ''),
            'version' => (string) ($row[self::D_BROWSER_VERSION] ?? ''),
            'sessions' => (int) ($row[self::M_SESSIONS] ?? 0),
            'percentage' => $total > 0
                ? round(((int) ($row[self::M_SESSIONS] ?? 0) / $total) * 100, 2)
                : 0.0,
        ])->values();
    }

    public function getCountryBreakdown(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        $response = $this->runReport(
            $range,
            [self::M_SESSIONS],
            [self::D_COUNTRY, self::D_COUNTRY_ID],
            [['metric' => self::M_SESSIONS, 'desc' => true]],
            $this->clampLimit($limit),
        );

        if ($response === null) {
            return collect();
        }

        $rows = $response->rows();
        $total = array_sum(array_map(fn (array $row): int => (int) ($row[self::M_SESSIONS] ?? 0), $rows));

        return collect($rows)->map(fn (array $row): array => [
            'country' => (string) ($row[self::D_COUNTRY] ?? ''),
            'country_code' => (string) ($row[self::D_COUNTRY_ID] ?? ''),
            'sessions' => (int) ($row[self::M_SESSIONS] ?? 0),
            'percentage' => $total > 0
                ? round(((int) ($row[self::M_SESSIONS] ?? 0) / $total) * 100, 2)
                : 0.0,
        ])->values();
    }

    public function getRealTimeVisitors(int $minutes = 5, array $filters = []): int
    {
        $connection = $this->resolver->googleConnection();

        if ($connection === null || $this->tokens === null) {
            return 0;
        }

        $property = $this->propertyId();

        if ($property === null) {
            return 0;
        }

        try {
            $accessToken = $this->tokens->getValidAccessToken($connection);
        } catch (TokenRefreshException) {
            return 0;
        }

        $minutesAgo = max(1, min(30, $minutes));
        $endpoint = sprintf(
            '%s/properties/%s:runRealtimeReport',
            rtrim((string) $this->config->get('analytics-google.reporting.api_base', 'https://analyticsdata.googleapis.com/v1beta'), '/'),
            rawurlencode($property),
        );

        $payload = [
            'metrics' => [['name' => 'activeUsers']],
            'minuteRanges' => [[
                'startMinutesAgo' => $minutesAgo,
                'endMinutesAgo' => 0,
            ]],
        ];

        try {
            $response = $this->http
                ->timeout((int) $this->config->get('analytics-google.reporting.timeout', 30))
                ->withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, $payload);
        } catch (ConnectionException) {
            return 0;
        }

        if (! $response->successful()) {
            return 0;
        }

        $body = $response->json();
        $rows = is_array($body) && isset($body['rows']) && is_array($body['rows']) ? $body['rows'] : [];
        $total = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $values = is_array($row['metricValues'] ?? null) ? $row['metricValues'] : [];
            $value = $values[0]['value'] ?? '0';

            if (is_numeric($value)) {
                $total += (int) $value;
            }
        }

        return $total;
    }

    public function getTopBotAgents(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        return collect();
    }

    /**
     * Event breakdown by eventName. Not on the interface — called directly by
     * DashboardDataSourceManager::getEventBreakdown when Google is active.
     *
     * @return Collection<int, array{name: string, category: string, count: int, total_value: float, percentage: float}>
     */
    public function eventBreakdown(DateRange $range, int $limit = 10): Collection
    {
        $response = $this->runReport(
            $range,
            [self::M_EVENT_COUNT],
            [self::D_EVENT_NAME],
            [['metric' => self::M_EVENT_COUNT, 'desc' => true]],
            $this->clampLimit($limit),
        );

        if ($response === null) {
            return collect();
        }

        $rows = $response->rows();
        $total = array_sum(array_map(fn (array $row): int => (int) ($row[self::M_EVENT_COUNT] ?? 0), $rows));

        return collect($rows)->map(fn (array $row): array => [
            'name' => (string) ($row[self::D_EVENT_NAME] ?? ''),
            'category' => '',
            'count' => (int) ($row[self::M_EVENT_COUNT] ?? 0),
            'total_value' => 0.0,
            'percentage' => $total > 0
                ? round(((int) ($row[self::M_EVENT_COUNT] ?? 0) / $total) * 100, 2)
                : 0.0,
        ])->values();
    }

    private function totalMetric(DateRange $range, string $metric): float
    {
        $response = $this->runReport($range, [$metric]);

        return $response === null ? 0.0 : $response->totalFor($metric);
    }

    /**
     * @param  list<string>  $metrics
     * @param  list<string>  $dimensions
     * @param  list<array{metric?: string, dimension?: string, desc?: bool}>  $orderBys
     */
    private function runReport(
        DateRange $range,
        array $metrics,
        array $dimensions = [],
        array $orderBys = [],
        ?int $limit = null,
    ): ?ReportResponse {
        $connection = $this->resolver->googleConnection();

        if ($connection === null || ! $this->client->isAvailable()) {
            return null;
        }

        try {
            return $this->client->runReport(
                ReportRequest::make(
                    range: $this->convertRange($range),
                    metrics: $metrics,
                    dimensions: $dimensions,
                    orderBys: $orderBys,
                    limit: $limit,
                ),
                $connection,
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function convertRange(DateRange $range): GaDateRange
    {
        return new GaDateRange(
            $range->startDate->format('Y-m-d'),
            $range->endDate->format('Y-m-d'),
        );
    }

    /**
     * @return array{0: string, 1: callable(string): string}
     */
    private function granularityConfig(string $granularity): array
    {
        return match ($granularity) {
            'hour' => [self::D_DATE_HOUR, static function (string $raw): string {
                if (strlen($raw) === 10 && ctype_digit($raw)) {
                    return sprintf(
                        '%s-%s-%s %s:00:00',
                        substr($raw, 0, 4),
                        substr($raw, 4, 2),
                        substr($raw, 6, 2),
                        substr($raw, 8, 2),
                    );
                }

                return $raw;
            }],
            'week' => [self::D_YEAR_WEEK, static fn (string $raw): string => $raw],
            'month' => [self::D_YEAR_MONTH, static function (string $raw): string {
                if (strlen($raw) === 6 && ctype_digit($raw)) {
                    return sprintf('%s-%s', substr($raw, 0, 4), substr($raw, 4, 2));
                }

                return $raw;
            }],
            default => [self::D_DATE, static function (string $raw): string {
                if (strlen($raw) === 8 && ctype_digit($raw)) {
                    return sprintf(
                        '%s-%s-%s',
                        substr($raw, 0, 4),
                        substr($raw, 4, 2),
                        substr($raw, 6, 2),
                    );
                }

                return $raw;
            }],
        };
    }

    /**
     * @return Collection<int, array{device_type: string, sessions: int, percentage: float}>
     */
    private function breakdownWithPercentage(?ReportResponse $response, string $dimension, string $metric, string $labelKey): Collection
    {
        if ($response === null) {
            return collect();
        }

        $rows = $response->rows();
        $total = array_sum(array_map(fn (array $row): int => (int) ($row[$metric] ?? 0), $rows));

        return collect($rows)->map(fn (array $row): array => [
            $labelKey => (string) ($row[$dimension] ?? ''),
            'sessions' => (int) ($row[$metric] ?? 0),
            'percentage' => $total > 0
                ? round(((int) ($row[$metric] ?? 0) / $total) * 100, 2)
                : 0.0,
        ])->values();
    }

    private function propertyId(): ?string
    {
        $id = (string) ($this->config->get('analytics-google.reporting.property_id') ?? '');

        return $id === '' ? null : $id;
    }

    private function clampLimit(int $limit): int
    {
        return max(1, min(100, $limit));
    }
}
