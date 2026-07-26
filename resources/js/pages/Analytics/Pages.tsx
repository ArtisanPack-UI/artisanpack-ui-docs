import { Head } from '@inertiajs/react';

import { AdminLayout } from '../../layouts/AdminLayout';
import { AnalyticsSubNav } from './components/AnalyticsSubNav';
import { DataTable, type DataTableColumn } from './components/DataTable';
import { PageViewsChart } from './components/PageViewsChart';
import { Panel } from './components/Panel';
import type { PageViewTimeSeriesPoint, TopPageItem, WrappedResource } from './types';
import { unwrap } from './unwrap';

interface PagesPageProps {
    topPages: WrappedResource<TopPageItem[]>;
    chartData: WrappedResource<PageViewTimeSeriesPoint[]>;
}

const COLUMNS: DataTableColumn<TopPageItem>[] = [
    {
        key: 'path',
        label: 'Page',
        render: (row) => (
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
        render: (row) => row.views.toLocaleString(),
    },
    {
        key: 'unique_views',
        label: 'Unique visitors',
        align: 'right',
        render: (row) => row.unique_views.toLocaleString(),
    },
];

export default function AnalyticsPagesPage(props: PagesPageProps) {
    const topPages = unwrap<TopPageItem[]>(props.topPages, []);
    const chartData = unwrap<PageViewTimeSeriesPoint[]>(props.chartData, []);

    return (
        <AdminLayout title="Analytics · Pages">
            <Head title="Analytics · Pages" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
                <header>
                    <p className="text-small text-text-muted">
                        Which pages the docs site is sending traffic to.
                    </p>
                </header>

                <AnalyticsSubNav />

                <Panel accent title="Pageviews over time">
                    <PageViewsChart data={chartData} />
                </Panel>

                <Panel title="Top pages" description={`${topPages.length} pages in view.`}>
                    <DataTable<TopPageItem>
                        rows={topPages}
                        columns={COLUMNS}
                        getRowKey={(row) => row.path}
                    />
                </Panel>
            </div>
        </AdminLayout>
    );
}
