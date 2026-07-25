import { Head, router } from '@inertiajs/react';
import { useMemo } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';
import { AnalyticsSubNav } from './components/AnalyticsSubNav';
import { DataTable, type DataTableColumn } from './components/DataTable';
import { PageViewsChart } from './components/PageViewsChart';
import { Panel } from './components/Panel';
import { StatTile } from './components/StatTile';
import type {
    DateRangeAwareProps,
    PageViewTimeSeriesPoint,
    StatsPayload,
    TopPageItem,
    TrafficSourceItem,
    WrappedResource,
} from './types';
import { unwrap } from './unwrap';

interface DashboardPageProps extends DateRangeAwareProps {
    stats: WrappedResource<StatsPayload>;
    chartData: WrappedResource<PageViewTimeSeriesPoint[]>;
    topPages: WrappedResource<TopPageItem[]>;
    trafficSources: WrappedResource<TrafficSourceItem[]>;
}

const EMPTY_STATS: StatsPayload = {
    pageviews: 0,
    visitors: 0,
    sessions: 0,
    bounce_rate: 0,
    avg_session_duration: 0,
    pages_per_session: 0,
    realtime_visitors: 0,
    comparison: null,
};

const DEFAULT_PRESETS: Record<string, string> = {
    today: 'Today',
    yesterday: 'Yesterday',
    '7d': 'Last 7 days',
    '30d': 'Last 30 days',
    '90d': 'Last 90 days',
    this_week: 'This week',
    last_week: 'Last week',
    this_month: 'This month',
    last_month: 'Last month',
    this_year: 'This year',
};

function formatDuration( seconds: number ): string {
    const total = Math.round( seconds );
    if ( total < 60 ) {
        return `${total}s`;
    }
    const minutes = Math.floor( total / 60 );
    const remaining = total % 60;
    return `${minutes}m ${remaining}s`;
}

const TOP_PAGES_COLUMNS: DataTableColumn<TopPageItem>[] = [
    {
        key: 'path',
        label: 'Page',
        render: ( row ) => (
            <div className="flex flex-col gap-0.5">
                <span className="font-mono text-text">{row.path || '/'}</span>
                {row.title ? (
                    <span className="text-xsmall text-text-subtle">{row.title}</span>
                ) : null}
            </div>
        ),
    },
    {
        key: 'views',
        label: 'Views',
        align: 'right',
        render: ( row ) => row.views.toLocaleString(),
    },
    {
        key: 'unique_views',
        label: 'Unique',
        align: 'right',
        render: ( row ) => row.unique_views.toLocaleString(),
    },
];

const TRAFFIC_COLUMNS: DataTableColumn<TrafficSourceItem>[] = [
    {
        key: 'source',
        label: 'Source',
        render: ( row ) => (
            <div className="flex flex-col gap-0.5">
                <span className="text-text">{row.source || 'Direct'}</span>
                {row.medium ? (
                    <span className="text-xsmall text-text-subtle">{row.medium}</span>
                ) : null}
            </div>
        ),
    },
    {
        key: 'visitors',
        label: 'Visitors',
        align: 'right',
        render: ( row ) => row.visitors.toLocaleString(),
    },
    {
        key: 'sessions',
        label: 'Sessions',
        align: 'right',
        render: ( row ) => row.sessions.toLocaleString(),
    },
];

export default function AnalyticsDashboardPage( props: DashboardPageProps ) {
    const stats = unwrap( props.stats, EMPTY_STATS );
    const chartData = unwrap<PageViewTimeSeriesPoint[]>( props.chartData, [] );
    const topPages = unwrap<TopPageItem[]>( props.topPages, [] );
    const trafficSources = unwrap<TrafficSourceItem[]>( props.trafficSources, [] );

    const presets = props.dateRangePresets ?? DEFAULT_PRESETS;
    const preset = props.dateRangePreset ?? '30d';

    const handlePresetChange = ( event: React.ChangeEvent<HTMLSelectElement> ): void => {
        router.get(
            '/dashboard/analytics',
            { period: event.target.value },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    };

    const tiles = useMemo(
        () => [
            {
                label: 'Pageviews',
                value: stats.pageviews.toLocaleString(),
                trend: stats.comparison?.pageviews ?? null,
            },
            {
                label: 'Visitors',
                value: stats.visitors.toLocaleString(),
                trend: stats.comparison?.visitors ?? null,
            },
            {
                label: 'Sessions',
                value: stats.sessions.toLocaleString(),
                trend: stats.comparison?.sessions ?? null,
            },
            {
                label: 'Bounce rate',
                value: `${( stats.bounce_rate ?? 0 ).toFixed( 1 )}%`,
                trend: stats.comparison?.bounce_rate
                    ? { ...stats.comparison.bounce_rate, positive: false }
                    : null,
            },
            {
                label: 'Avg. session',
                value: formatDuration( stats.avg_session_duration ),
                trend: stats.comparison?.avg_session_duration ?? null,
            },
        ],
        [ stats ],
    );

    return (
        <AdminLayout title="Analytics">
            <Head title="Analytics" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <p className="text-small text-text-muted">
                        Traffic, engagement, and events across the docs site.
                    </p>
                    <label className="flex items-center gap-2 text-small text-text-muted">
                        <span>Range</span>
                        <select
                            value={preset}
                            onChange={handlePresetChange}
                            className="rounded-[8px] border border-border-subtle bg-surface px-3 py-1.5 text-small text-text focus:border-secondary focus:outline-none"
                        >
                            {Object.entries( presets ).map( ( [ id, label ] ) => (
                                <option key={id} value={id}>
                                    {label}
                                </option>
                            ) )}
                        </select>
                    </label>
                </header>

                <AnalyticsSubNav />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {tiles.map( ( tile ) => (
                        <StatTile
                            key={tile.label}
                            label={tile.label}
                            value={tile.value}
                            trend={tile.trend}
                            hint="vs previous period"
                        />
                    ) )}
                </div>

                <Panel
                    accent
                    title="Pageviews & visitors"
                    description="Daily trend over the selected range."
                >
                    <PageViewsChart data={chartData} />
                </Panel>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Panel title="Top pages" description="Most-viewed docs pages.">
                        <DataTable<TopPageItem>
                            rows={topPages}
                            columns={TOP_PAGES_COLUMNS}
                            getRowKey={( row ) => row.path}
                        />
                    </Panel>
                    <Panel title="Traffic sources" description="Where visitors arrived from.">
                        <DataTable<TrafficSourceItem>
                            rows={trafficSources}
                            columns={TRAFFIC_COLUMNS}
                            getRowKey={( row, index ) => `${row.source}-${row.medium}-${index}`}
                        />
                    </Panel>
                </div>
            </div>
        </AdminLayout>
    );
}
