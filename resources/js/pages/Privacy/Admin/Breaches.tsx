import { Head } from '@inertiajs/react';
import { AdminBreachManager } from '@artisanpack-ui/privacy/react';

import { AdminLayout } from '@/layouts/AdminLayout';

interface BreachesProps {
    endpoint: string;
    report_url: string;
    detail_url_template: string;
}

export default function Breaches({ endpoint, report_url, detail_url_template }: BreachesProps) {
    const buildDetailUrl = (id: number) => detail_url_template.replace('__ID__', String(id));

    return (
        <AdminLayout title="Privacy · Breaches">
            <Head title="Privacy Breaches" />
            <div className="privacy-admin-shell mx-auto w-full max-w-6xl">
                <AdminBreachManager
                    endpoint={endpoint}
                    reportUrl={report_url}
                    detailUrlBuilder={buildDetailUrl}
                />
            </div>
        </AdminLayout>
    );
}
