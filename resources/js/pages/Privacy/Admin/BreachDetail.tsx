import { Head, Link } from '@inertiajs/react';
import { AdminBreachDetail } from '@artisanpack-ui/privacy/react';

import { AdminLayout } from '@/layouts/AdminLayout';
import { csrfToken } from '@/lib/csrf';

interface BreachDetailProps {
    breach_id: number;
    endpoint: string;
    index_url: string;
}

export default function BreachDetail({ breach_id, endpoint, index_url }: BreachDetailProps) {
    return (
        <AdminLayout title="Privacy · Breach detail">
            <Head title="Breach Detail" />
            <div className="privacy-admin-shell mx-auto flex w-full max-w-5xl flex-col gap-4">
                <Link href={index_url} className="text-small text-text-muted hover:text-secondary">
                    ← Back to breaches
                </Link>
                <AdminBreachDetail
                    breachId={breach_id}
                    endpoint={endpoint}
                    csrfToken={csrfToken()}
                />
            </div>
        </AdminLayout>
    );
}
