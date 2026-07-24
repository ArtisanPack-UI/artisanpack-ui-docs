import { Head, router } from '@inertiajs/react';
import { AdminBreachReportForm } from '@artisanpack-ui/privacy/react';

import { AdminLayout } from '@/layouts/AdminLayout';
import { csrfToken } from '@/lib/csrf';

interface BreachReportProps {
    endpoint: string;
    index_url: string;
}

export default function BreachReport({ endpoint, index_url }: BreachReportProps) {
    return (
        <AdminLayout title="Privacy · Report breach">
            <Head title="Report Breach" />
            <div className="privacy-admin-shell mx-auto w-full max-w-3xl">
                <AdminBreachReportForm
                    endpoint={endpoint}
                    csrfToken={csrfToken()}
                    onSubmitted={() => router.visit(index_url)}
                />
            </div>
        </AdminLayout>
    );
}
