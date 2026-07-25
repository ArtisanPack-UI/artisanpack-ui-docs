import { Head } from '@inertiajs/react';

import { AdminLayout } from '../../layouts/AdminLayout';
import { AnalyticsSubNav } from './components/AnalyticsSubNav';
import { DataTable, type DataTableColumn } from './components/DataTable';
import { Panel } from './components/Panel';
import type { TrafficSourceItem, WrappedResource } from './types';
import { unwrap } from './unwrap';

interface TrafficPageProps {
    trafficSources: WrappedResource<TrafficSourceItem[]>;
}

const COLUMNS: DataTableColumn<TrafficSourceItem>[] = [
    {
        key: 'source',
        label: 'Source',
        render: ( row ) => row.source || 'Direct',
    },
    {
        key: 'medium',
        label: 'Medium',
        render: ( row ) => row.medium || '—',
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

export default function AnalyticsTrafficPage( props: TrafficPageProps ) {
    const trafficSources = unwrap<TrafficSourceItem[]>( props.trafficSources, [] );

    return (
        <AdminLayout title="Analytics · Traffic">
            <Head title="Analytics · Traffic" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
                <header>
                    <p className="text-small text-text-muted">
                        Where visitors are coming from — referrers, campaigns, and direct traffic.
                    </p>
                </header>

                <AnalyticsSubNav />

                <Panel accent title="Traffic sources">
                    <DataTable<TrafficSourceItem>
                        rows={trafficSources}
                        columns={COLUMNS}
                        getRowKey={( row, index ) => `${row.source}-${row.medium}-${index}`}
                    />
                </Panel>
            </div>
        </AdminLayout>
    );
}
