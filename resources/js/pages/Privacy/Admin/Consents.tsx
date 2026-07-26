import { Head } from '@inertiajs/react';
import { AdminConsentManager } from '@artisanpack-ui/privacy/react';

import { AdminLayout } from '@/layouts/AdminLayout';
import { csrfToken } from '@/lib/csrf';

interface ConsentsProps {
    endpoint: string;
}

export default function Consents({ endpoint }: ConsentsProps) {
    return (
        <AdminLayout title="Privacy · Consents">
            <Head title="Privacy Consents" />
            <div className="privacy-admin-shell mx-auto w-full max-w-6xl">
                <AdminConsentManager endpoint={endpoint} csrfToken={csrfToken()} />
            </div>
        </AdminLayout>
    );
}
