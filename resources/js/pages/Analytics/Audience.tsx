import { Head } from '@inertiajs/react';

import { AdminLayout } from '../../layouts/AdminLayout';
import { AnalyticsSubNav } from './components/AnalyticsSubNav';
import { DataTable, type DataTableColumn } from './components/DataTable';
import { Panel } from './components/Panel';
import type {
    BrowserBreakdownItem,
    CountryBreakdownItem,
    DeviceBreakdownItem,
    WrappedResource,
} from './types';
import { unwrap } from './unwrap';

interface AudiencePageProps {
    deviceBreakdown: WrappedResource<DeviceBreakdownItem[]>;
    browserBreakdown: WrappedResource<BrowserBreakdownItem[]>;
    countryBreakdown: WrappedResource<CountryBreakdownItem[]>;
}

interface Row {
    label: string;
    visitors: number;
    percentage?: number;
}

const COLUMNS: DataTableColumn<Row>[] = [
    { key: 'label', label: 'Segment', render: (row) => row.label },
    {
        key: 'visitors',
        label: 'Visitors',
        align: 'right',
        render: (row) => row.visitors.toLocaleString(),
    },
    {
        key: 'percentage',
        label: 'Share',
        align: 'right',
        render: (row) =>
            typeof row.percentage === 'number' ? `${row.percentage.toFixed(1)}%` : '—',
    },
];

export default function AnalyticsAudiencePage(props: AudiencePageProps) {
    const devices = unwrap<DeviceBreakdownItem[]>(props.deviceBreakdown, []).map((d) => ({
        label: d.device,
        visitors: d.visitors,
        percentage: d.percentage,
    }));
    const browsers = unwrap<BrowserBreakdownItem[]>(props.browserBreakdown, []).map((b) => ({
        label: b.browser,
        visitors: b.visitors,
        percentage: b.percentage,
    }));
    const countries = unwrap<CountryBreakdownItem[]>(props.countryBreakdown, []).map((c) => ({
        label: c.country ?? c.country_code ?? 'Unknown',
        visitors: c.visitors,
        percentage: c.percentage,
    }));

    return (
        <AdminLayout title="Analytics · Audience">
            <Head title="Analytics · Audience" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
                <header>
                    <p className="text-small text-text-muted">
                        Devices, browsers, and locations of the visitors reading the docs.
                    </p>
                </header>

                <AnalyticsSubNav />

                <div className="grid gap-6 lg:grid-cols-2">
                    <Panel title="Devices">
                        <DataTable<Row>
                            rows={devices}
                            columns={COLUMNS}
                            getRowKey={(row) => `device-${row.label}`}
                        />
                    </Panel>
                    <Panel title="Browsers">
                        <DataTable<Row>
                            rows={browsers}
                            columns={COLUMNS}
                            getRowKey={(row) => `browser-${row.label}`}
                        />
                    </Panel>
                </div>

                <Panel accent title="Countries">
                    <DataTable<Row>
                        rows={countries}
                        columns={COLUMNS}
                        getRowKey={(row) => `country-${row.label}`}
                    />
                </Panel>
            </div>
        </AdminLayout>
    );
}
