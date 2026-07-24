import { Head, Link, router } from '@inertiajs/react';
import { Alert } from '@artisanpack-ui/react/feedback';

import { AdminLayout } from '../../../../../../../resources/js/layouts/AdminLayout';
import {
    DocsOrderer,
    type DocsOrdererChangeItem,
    type DocsOrdererItem,
} from '../../../../../../../resources/js/components/DocsOrderer';

interface PackagePayload {
    id: number;
    name: string;
    slug: string;
}

interface ManageProps {
    package: PackagePayload;
    documentation: DocsOrdererItem[];
    reorder_url: string;
    back_url: string;
    flash?: { success?: string | null };
}

export default function ManageDocumentation({
    package: pkg,
    documentation,
    reorder_url,
    back_url,
    flash,
}: ManageProps) {
    const handleReorder = (items: DocsOrdererChangeItem[]) => {
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
        <AdminLayout title={`Documentation — ${pkg.name}`}>
            <Head title={`Documentation: ${pkg.name}`} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6">
                <header className="flex items-start justify-between gap-4">
                    <div className="flex flex-col gap-1">
                        <h2 className="font-display text-h6">Documentation Order</h2>
                        <p className="text-small text-text-muted">
                            Drag and drop documentation pages to reorder them. Child pages will stay with their parent page.
                        </p>
                    </div>
                    <Link href={back_url} className="btn btn-ghost">
                        ← Back to package
                    </Link>
                </header>

                {flash?.success ? <Alert color="success">{flash.success}</Alert> : null}

                <div className="ap-box ap-border-gradient p-6">
                    <DocsOrderer items={documentation} onChange={handleReorder} />
                </div>
            </div>
        </AdminLayout>
    );
}
