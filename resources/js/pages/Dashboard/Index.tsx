import { Head } from '@inertiajs/react';
import { Card } from '@artisanpack-ui/react/layout';

import { AdminLayout } from '../../layouts/AdminLayout';

export default function DashboardIndex() {
    return (
        <AdminLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <header>
                    <p className="text-small text-text-muted">
                        Manage packages, pages, and site settings from a single place.
                    </p>
                </header>

                <Card>
                    <h2 className="mb-2 font-display text-h6">Welcome back</h2>
                    <p className="text-small text-text-muted">
                        Use the sidebar to navigate the admin area. Package, documentation,
                        and page management ship in upcoming releases.
                    </p>
                </Card>
            </div>
        </AdminLayout>
    );
}
