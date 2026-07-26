/**
 * Prop shapes returned by `ArtisanPackUI\Analytics\Http\Controllers\InertiaDashboardController`.
 * Each field maps to a Laravel API Resource that wraps its `toArray()` in
 * `{ data: … }`, so all props enter these pages as `T | { data: T }` and
 * are normalised via `./unwrap.ts` before render.
 */

export interface DateRange {
    start: string;
    end: string;
}

export interface StatsComparisonMetric {
    value: number;
    change: number;
    trend: 'up' | 'down' | 'neutral';
    positive: boolean;
}

export interface StatsPayload {
    pageviews: number;
    visitors: number;
    sessions: number;
    bounce_rate: number;
    avg_session_duration: number;
    pages_per_session?: number;
    realtime_visitors?: number;
    comparison?: {
        pageviews?: StatsComparisonMetric;
        visitors?: StatsComparisonMetric;
        sessions?: StatsComparisonMetric;
        bounce_rate?: StatsComparisonMetric;
        avg_session_duration?: StatsComparisonMetric;
    } | null;
}

export interface PageViewTimeSeriesPoint {
    date: string;
    pageviews: number;
    visitors: number;
}

export interface TopPageItem {
    path: string;
    title: string;
    views: number;
    unique_views: number;
}

export interface TrafficSourceItem {
    source: string;
    medium: string;
    sessions: number;
    visitors: number;
}

export interface DeviceBreakdownItem {
    device: string;
    visitors: number;
    percentage?: number;
}

export interface BrowserBreakdownItem {
    browser: string;
    visitors: number;
    percentage?: number;
}

export interface CountryBreakdownItem {
    country: string;
    country_code?: string;
    visitors: number;
    percentage?: number;
}

export interface EventBreakdownItem {
    name: string;
    category: string;
    count: number;
    total_value: number;
    percentage: number;
}

export interface RealtimePayload {
    active_visitors: number;
    recent_pageviews?: Array<{
        path: string;
        title?: string | null;
        timestamp: string;
        visitor_id?: string | null;
    }>;
    /** Length of the "recent" window in minutes (echoed by the feed endpoint). */
    window_minutes?: number;
}

export type WrappedResource<T> = T | { data: T };

export interface DateRangeAwareProps {
    dateRange?: DateRange;
    dateRangePreset?: string;
    dateRangePresets?: Record<string, string>;
    filters?: Record<string, unknown>;
    includeBots?: boolean;
}
