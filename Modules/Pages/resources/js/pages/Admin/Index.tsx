import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Table, type TableHeader } from '@artisanpack-ui/react/data';
import { Alert } from '@artisanpack-ui/react/feedback';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';

interface PageRow extends Record<string, unknown> {
    id: number;
    title: string;
    slug: string;
    edit_url: string;
    destroy_url: string;
}

interface IndexProps {
    pages: PageRow[];
    create_url: string;
    menu_order_url: string;
    flash?: { success?: string | null };
}

const HEADERS: TableHeader<PageRow>[] = [
    { key: 'title', label: 'Title' },
    { key: 'slug', label: 'Slug' },
];

export default function PagesIndex({ pages, create_url, menu_order_url, flash }: IndexProps) {
    const handleDelete = (page: PageRow) => {
        if (!window.confirm(`Delete page "${page.title}"?`)) {
            return;
        }
        router.delete(page.destroy_url, { preserveScroll: true });
    };

    return (
        <AdminLayout title="Pages">
            <Head title="Pages" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6">
                <header className="flex items-start justify-between gap-4">
                    <p className="text-small text-text-muted">
                        Manage the pages surfaced across the docs site.
                    </p>
                    <div className="flex gap-2">
                        <Link href={menu_order_url} className="btn">
                            Menu Order
                        </Link>
                        <Link href={create_url} className="btn btn-primary">
                            Add Page
                        </Link>
                    </div>
                </header>

                {flash?.success ? (
                    <Alert color="success">{flash.success}</Alert>
                ) : null}

                <Card>
                    <Table<PageRow>
                        headers={HEADERS}
                        rows={pages}
                        emptyText="No pages yet. Add one to get started."
                        renderActions={(page) => (
                            <div className="flex justify-end gap-2">
                                <Link href={page.edit_url} className="btn btn-sm">
                                    Edit
                                </Link>
                                <Button
                                    size="sm"
                                    color="error"
                                    onClick={() => handleDelete(page)}
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
