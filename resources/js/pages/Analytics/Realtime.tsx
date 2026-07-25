import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';
import { AnalyticsSubNav } from './components/AnalyticsSubNav';
import { DataTable, type DataTableColumn } from './components/DataTable';
import { Panel } from './components/Panel';
import { StatTile } from './components/StatTile';
import type { RealtimePayload } from './types';

interface RealtimePageProps {
    realtime: RealtimePayload;
}

type RecentPageView = NonNullable<RealtimePayload['recent_pageviews']>[number];

const COLUMNS: DataTableColumn<RecentPageView>[] = [
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
        key: 'timestamp',
        label: 'When',
        align: 'right',
        render: ( row ) => {
            try {
                return new Date( row.timestamp ).toLocaleTimeString();
            } catch {
                return row.timestamp;
            }
        },
    },
];

export default function AnalyticsRealtimePage( { realtime }: RealtimePageProps ) {
    const [ data, setData ] = useState<RealtimePayload>( realtime );

    useEffect( () => {
        const controller = new AbortController();
        const timer = window.setInterval( async () => {
            try {
                // App-side companion of the vendor realtime endpoint —
                // supplements `active_visitors` with a `recent_pageviews`
                // list queried directly from `analytics_page_views`.
                // See `App\Http\Controllers\Analytics\RealtimeController::feed`.
                const response = await fetch( '/dashboard/analytics/realtime/feed', {
                    signal: controller.signal,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                } );
                if ( ! response.ok ) {
                    return;
                }
                const next = ( await response.json() ) as RealtimePayload;
                setData( next );
            } catch {
                // Aborted or offline — the next tick will retry.
            }
        }, 10_000 );

        return () => {
            window.clearInterval( timer );
            controller.abort();
        };
    }, [] );

    return (
        <AdminLayout title="Analytics · Realtime">
            <Head title="Analytics · Realtime" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
                <header>
                    <p className="text-small text-text-muted">
                        Visitors currently active on the docs site. Refreshes every 10 seconds.
                    </p>
                </header>

                <AnalyticsSubNav />

                <div className="grid gap-4 sm:grid-cols-2">
                    <StatTile
                        label="Active visitors"
                        value={data.active_visitors.toLocaleString()}
                        hint={`in the last ${data.window_minutes ?? 5} minutes`}
                    />
                    <StatTile
                        label="Recent pageviews"
                        value={( data.recent_pageviews?.length ?? 0 ).toLocaleString()}
                        hint={`most recent samples (max 20) in the last ${data.window_minutes ?? 5} minutes`}
                    />
                </div>

                <Panel accent title="Recent pageviews">
                    <DataTable<RecentPageView>
                        rows={data.recent_pageviews ?? []}
                        columns={COLUMNS}
                        getRowKey={( row, index ) => `${row.path}-${row.timestamp}-${index}`}
                    />
                </Panel>
            </div>
        </AdminLayout>
    );
}
