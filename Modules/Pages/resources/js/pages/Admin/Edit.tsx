import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button, Input, Select, Textarea } from '@artisanpack-ui/react/form';
import { Alert } from '@artisanpack-ui/react/feedback';
import type { FormEventHandler } from 'react';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';
import { RichTextEditor } from '../../../../../../resources/js/components/RichTextEditor';

interface ParentOption {
    id: number;
    name: string;
    [key: string]: string | number;
}

interface PagePayload {
    id: number;
    title: string;
    slug: string;
    content: string;
    meta_description: string;
    parent: number | null;
    menu_order: number;
    icon: string;
}

interface EditProps {
    page: PagePayload;
    parent_options: ParentOption[];
    update_url: string;
    destroy_url: string;
    index_url: string;
    flash?: { success?: string | null };
}

interface PageForm {
    title: string;
    slug: string;
    content: string;
    meta_description: string;
    parent: string;
    menu_order: string;
    icon: string;
    [key: string]: string;
}

function GradientCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="ap-box ap-border-gradient flex flex-col gap-4 p-6">
            <h2 className="font-display text-h6">{title}</h2>
            {children}
        </section>
    );
}

export default function PagesEdit({
    page,
    parent_options,
    update_url,
    destroy_url,
    index_url,
    flash,
}: EditProps) {
    const { data, setData, patch, processing, errors } = useForm<PageForm>({
        title: page.title,
        slug: page.slug,
        content: page.content,
        meta_description: page.meta_description,
        parent: page.parent === null ? '' : String(page.parent),
        menu_order: String(page.menu_order),
        icon: page.icon,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(update_url, { preserveScroll: true });
    };

    const handleDelete = () => {
        if (!window.confirm(`Delete page "${page.title}"?`)) {
            return;
        }
        router.delete(destroy_url);
    };

    return (
        <AdminLayout title="Edit Page">
            <Head title={`Edit ${page.title}`} />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-6xl flex-col gap-6" noValidate>
                {flash?.success ? <Alert color="success">{flash.success}</Alert> : null}

                <div className="flex flex-col gap-6 md:flex-row">
                    <div className="flex flex-1 flex-col gap-6 md:flex-[2]">
                        <GradientCard title="Page">
                            <Input
                                id="title"
                                label="Title"
                                required
                                value={data.title}
                                onChange={(event) => setData('title', event.target.value)}
                                error={errors.title}
                            />
                            <Input
                                id="slug"
                                label="Slug"
                                required
                                value={data.slug}
                                onChange={(event) => setData('slug', event.target.value)}
                                error={errors.slug}
                            />
                            <RichTextEditor
                                id="content"
                                label="Content"
                                required
                                value={data.content}
                                onValueChange={(value) => setData('content', value)}
                                error={errors.content}
                            />

                            <div className="flex flex-wrap items-center gap-3 pt-2">
                                <Link href={index_url} className="btn btn-ghost">
                                    Back
                                </Link>
                                <Button
                                    type="button"
                                    color="error"
                                    onClick={handleDelete}
                                    className="ml-auto"
                                >
                                    Delete Page
                                </Button>
                            </div>
                        </GradientCard>
                    </div>

                    <div className="flex flex-1 flex-col gap-6">
                        <GradientCard title="Page Details">
                            <Select
                                id="parent"
                                label="Parent Page"
                                placeholder="No parent"
                                options={parent_options}
                                optionValue="id"
                                optionLabel="name"
                                value={data.parent}
                                onChange={(event) => setData('parent', event.target.value)}
                                error={errors.parent}
                            />
                            <Input
                                id="menu_order"
                                type="number"
                                label="Order"
                                value={data.menu_order}
                                onChange={(event) => setData('menu_order', event.target.value)}
                                error={errors.menu_order}
                            />
                            <Input
                                id="icon"
                                label="Icon"
                                value={data.icon}
                                onChange={(event) => setData('icon', event.target.value)}
                                error={errors.icon}
                            />
                            <Textarea
                                id="meta_description"
                                label="Meta Description"
                                hint="SEO description (max 160 characters)."
                                maxLength={160}
                                rows={3}
                                value={data.meta_description}
                                onChange={(event) => setData('meta_description', event.target.value)}
                                error={errors.meta_description}
                            />

                            <div className="flex justify-end pt-2">
                                <Button type="submit" color="primary" loading={processing}>
                                    Update Page
                                </Button>
                            </div>
                        </GradientCard>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
