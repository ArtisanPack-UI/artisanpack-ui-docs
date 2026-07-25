<?php

declare(strict_types=1);

namespace App\Analytics;

use ArtisanPackUI\Analytics\Analytics;
use ArtisanPackUI\Analytics\Data\DateRange;
use ArtisanPackUI\Analytics\Services\AnalyticsQuery;
use Illuminate\Support\Collection;

/**
 * Container-bound replacement for the vendor AnalyticsQuery service.
 *
 * Extends the vendor class so InertiaDashboardController's typehint still
 * resolves, then dispatches each dashboard-facing method to either the
 * parent (local) implementation or a private google-scoped AnalyticsQuery
 * built around Ga4QueryProvider.
 *
 * The `_source` filter injected into google-scoped calls differentiates
 * cache keys so a source switch never surfaces stale rows from the other
 * provider.
 */
class DashboardDataSourceManager extends AnalyticsQuery
{
    private readonly AnalyticsQuery $googleQuery;

    public function __construct(
        Analytics $localProvider,
        private readonly Ga4QueryProvider $googleProvider,
        private readonly AnalyticsSourceResolver $resolver,
    ) {
        parent::__construct($localProvider);
        $this->googleQuery = new AnalyticsQuery($this->googleProvider);
    }

    public function getStats(DateRange $range, bool $withCompare = true, array $filters = []): array
    {
        if ($this->useGoogle()) {
            return $this->googleQuery->getStats($range, $withCompare, $this->tagFilters($filters));
        }

        return parent::getStats($range, $withCompare, $filters);
    }

    public function getPageViews(DateRange $range, string $granularity = 'day', array $filters = []): Collection
    {
        if ($this->useGoogle()) {
            return $this->googleQuery->getPageViews($range, $granularity, $this->tagFilters($filters));
        }

        return parent::getPageViews($range, $granularity, $filters);
    }

    public function getTopPages(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        if ($this->useGoogle()) {
            return $this->googleQuery->getTopPages($range, $limit, $this->tagFilters($filters));
        }

        return parent::getTopPages($range, $limit, $filters);
    }

    public function getTrafficSources(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        if ($this->useGoogle()) {
            return $this->googleQuery->getTrafficSources($range, $limit, $this->tagFilters($filters));
        }

        return parent::getTrafficSources($range, $limit, $filters);
    }

    public function getDeviceBreakdown(DateRange $range, array $filters = []): Collection
    {
        if ($this->useGoogle()) {
            return $this->googleQuery->getDeviceBreakdown($range, $this->tagFilters($filters));
        }

        return parent::getDeviceBreakdown($range, $filters);
    }

    public function getBrowserBreakdown(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        if ($this->useGoogle()) {
            return $this->googleQuery->getBrowserBreakdown($range, $limit, $this->tagFilters($filters));
        }

        return parent::getBrowserBreakdown($range, $limit, $filters);
    }

    public function getCountryBreakdown(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        if ($this->useGoogle()) {
            return $this->googleQuery->getCountryBreakdown($range, $limit, $this->tagFilters($filters));
        }

        return parent::getCountryBreakdown($range, $limit, $filters);
    }

    public function getEventBreakdown(DateRange $range, int $limit = 10, array $filters = []): Collection
    {
        if ($this->useGoogle()) {
            return $this->googleProvider->eventBreakdown($range, $limit);
        }

        return parent::getEventBreakdown($range, $limit, $filters);
    }

    public function getRealtime(int $minutes = 5, array $filters = []): array
    {
        if ($this->useGoogle()) {
            return $this->googleQuery->getRealtime($minutes, $this->tagFilters($filters));
        }

        return parent::getRealtime($minutes, $filters);
    }

    public function activeSource(): AnalyticsSource
    {
        return $this->useGoogle() ? AnalyticsSource::Google : AnalyticsSource::Local;
    }

    private function useGoogle(): bool
    {
        return $this->resolver->active() === AnalyticsSource::Google
            && $this->resolver->isGoogleAvailable();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function tagFilters(array $filters): array
    {
        $filters['_source'] = AnalyticsSource::Google->value;

        return $filters;
    }
}
