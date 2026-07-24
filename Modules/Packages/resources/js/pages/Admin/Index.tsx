import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Table, type TableHeader } from '@artisanpack-ui/react/data';
import { Alert } from '@artisanpack-ui/react/feedback';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';

interface PackageRow extends Record<string, unknown> {
    id: number;
    name: string;
    slug: string;
    version: string | null;
    package_registry: string | null;
    edit_url: string;
    destroy_url: string;
}

interface IndexProps {
    packages: PackageRow[];
    create_url: string;
    flash?: { success?: string | null };
}

const HEADERS: TableHeader<PackageRow>[] = [
    { key: 'name', label: 'Name' },
    { key: 'slug', label: 'Slug' },
    {
        key: 'version',
        label: 'Version',
        render: (value) => (value as string | null) ?? '—',
    },
    {
        key: 'package_registry',
        label: 'Registry',
        render: (value) => {
            const registry = value as string | null;
            if (registry === 'packagist') {
                return 'Packagist';
            }
            if (registry === 'npm') {
                return 'NPM';
            }
            return '—';
        },
    },
];

export default function PackagesIndex({ packages, create_url, flash }: IndexProps) {
    const handleDelete = (pkg: PackageRow) => {
        if (!window.confirm(`Delete package "${pkg.name}"?`)) {
            return;
        }
        router.delete(pkg.destroy_url, { preserveScroll: true });
    };

    return (
        <AdminLayout title="Packages">
            <Head title="Packages" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6">
                <header className="flex items-start justify-between gap-4">
                    <p className="text-small text-text-muted">
                        Manage the packages surfaced across the docs site.
                    </p>
                    <Link href={create_url} className="btn btn-primary">
                        Add Package
                    </Link>
                </header>

                {flash?.success ? (
                    <Alert color="success">{flash.success}</Alert>
                ) : null}

                <Card>
                    <Table<PackageRow>
                        headers={HEADERS}
                        rows={packages}
                        emptyText="No packages yet. Add one to get started."
                        renderActions={(pkg) => (
                            <div className="flex justify-end gap-2">
                                <Link href={pkg.edit_url} className="btn btn-sm">
                                    Edit
                                </Link>
                                <Button
                                    size="sm"
                                    color="error"
                                    onClick={() => handleDelete(pkg)}
                                >
                                    Delete
                                </Button>
                            </div>
                        )}
                    />
                </Card>
            </div>
        </AdminLayout>
    );
}
