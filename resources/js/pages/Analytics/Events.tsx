import { Head } from '@inertiajs/react';

import { AdminLayout } from '../../layouts/AdminLayout';
import { AnalyticsSubNav } from './components/AnalyticsSubNav';
import { DataTable, type DataTableColumn } from './components/DataTable';
import { Panel } from './components/Panel';
import type { EventBreakdownItem, WrappedResource } from './types';
import { unwrap } from './unwrap';

interface EventsPageProps {
    eventBreakdown: WrappedResource<EventBreakdownItem[]>;
}

const COLUMNS: DataTableColumn<EventBreakdownItem>[] = [
    {
        key: 'name',
        label: 'Event',
        render: (row) => <span className="font-mono text-text">{row.name}</span>,
    },
    {
        key: 'category',
        label: 'Category',
        render: (row) => row.category || '—',
    },
    {
        key: 'count',
        label: 'Count',
        align: 'right',
        render: (row) => row.count.toLocaleString(),
    },
    {
        key: 'percentage',
        label: 'Share',
        align: 'right',
        render: (row) => `${row.percentage.toFixed(1)}%`,
    },
];

export default function AnalyticsEventsPage(props: EventsPageProps) {
    const eventBreakdown = unwrap<EventBreakdownItem[]>(props.eventBreakdown, []);

    return (
        <AdminLayout title="Analytics · Events">
            <Head title="Analytics · Events" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
                <header>
                    <p className="text-small text-text-muted">
                        Custom events fired from the docs site — searches, code copies, outbound
                        clicks, and downloads.
                    </p>
                </header>

                <AnalyticsSubNav />

                <Panel accent title="Top events">
                    <DataTable<EventBreakdownItem>
                        rows={eventBreakdown}
                        columns={COLUMNS}
                        getRowKey={(row) => `${row.name}-${row.category}`}
                        emptyText="No events recorded in this range. Events will appear as visitors interact with the docs (search, copy code snippets, follow outbound links)."
                    />
                </Panel>
            </div>
        </AdminLayout>
    );
}
