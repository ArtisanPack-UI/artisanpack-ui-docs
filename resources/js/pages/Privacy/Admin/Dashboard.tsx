import { Head, Link } from '@inertiajs/react';

import { AdminLayout } from '@/layouts/AdminLayout';

interface DashboardProps {
    links: { label: string; href: string }[];
}

export default function PrivacyAdminDashboard({ links }: DashboardProps) {
    return (
        <AdminLayout title="Privacy">
            <Head title="Privacy" />

            <div className="mx-auto grid w-full max-w-5xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {links.map((link) => (
                    <Link
                        key={link.href}
                        href={link.href}
                        className="flex flex-col gap-1 rounded-[12px] border border-border-subtle bg-surface-2/70 p-5 text-text transition hover:border-primary/40"
                    >
                        <span className="font-display text-base font-semibold text-text">
                            {link.label}
                        </span>
                        <span className="text-small text-text-muted">Open →</span>
                    </Link>
                ))}
            </div>
        </AdminLayout>
    );
}
