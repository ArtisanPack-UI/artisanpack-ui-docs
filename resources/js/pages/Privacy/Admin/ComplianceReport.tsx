import { Head } from '@inertiajs/react';
import { AdminComplianceReport } from '@artisanpack-ui/privacy/react';

import { AdminLayout } from '@/layouts/AdminLayout';

interface ComplianceReportProps {
    endpoint: string;
}

export default function ComplianceReport({ endpoint }: ComplianceReportProps) {
    return (
        <AdminLayout title="Privacy · Compliance report">
            <Head title="Privacy Compliance Report" />
            <div className="privacy-admin-shell mx-auto w-full max-w-6xl">
                <AdminComplianceReport endpoint={endpoint} />
            </div>
        </AdminLayout>
    );
}
