import { Head } from '@inertiajs/react';
import { AdminDataRequestManager } from '@artisanpack-ui/privacy/react';

import { AdminLayout } from '@/layouts/AdminLayout';
import { csrfToken } from '@/lib/csrf';

interface DataRequestsProps {
    endpoint: string;
}

export default function DataRequests({ endpoint }: DataRequestsProps) {
    return (
        <AdminLayout title="Privacy · Data requests">
            <Head title="Privacy Data Requests" />
            <div className="privacy-admin-shell mx-auto w-full max-w-6xl">
                <AdminDataRequestManager endpoint={endpoint} csrfToken={csrfToken()} />
            </div>
        </AdminLayout>
    );
}
