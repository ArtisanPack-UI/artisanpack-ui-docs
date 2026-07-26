import { Head, Link, router } from '@inertiajs/react';
import { Alert } from '@artisanpack-ui/react/feedback';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';
import {
    PageOrderer,
    type PageOrdererChangeItem,
    type PageOrdererItem,
} from '../../../../../../resources/js/components/PageOrderer';

interface MenuOrderProps {
    pages: PageOrdererItem[];
    reorder_url: string;
    back_url: string;
    flash?: { success?: string | null };
}

export default function PagesMenuOrder({ pages, reorder_url, back_url, flash }: MenuOrderProps) {
    const handleReorder = (items: PageOrdererChangeItem[]) => {
        router.post(
            reorder_url,
            { items },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    return (
        <AdminLayout title="Manage Page Menu Order">
            <Head title="Manage Page Menu Order" />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
                <header className="flex items-start justify-between gap-4">
                    <div className="flex flex-col gap-1">
                        <h2 className="font-display text-h6">Page Order</h2>
                        <p className="text-small text-text-muted">
                            Drag and drop pages to reorder them. Child pages will stay with their
                            parent page.
                        </p>
                    </div>
                    <Link href={back_url} className="btn btn-ghost">
                        ← Back to Pages
                    </Link>
                </header>

                {flash?.success ? <Alert color="success">{flash.success}</Alert> : null}

                <div className="ap-box ap-border-gradient p-6">
                    <PageOrderer items={pages} onChange={handleReorder} />
                </div>
            </div>
        </AdminLayout>
    );
}
