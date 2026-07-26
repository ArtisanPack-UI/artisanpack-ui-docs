import { Head, Link, useForm } from '@inertiajs/react';
import { Button, Input, Select, Textarea } from '@artisanpack-ui/react/form';
import { useEffect, type FormEventHandler } from 'react';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';
import { RichTextEditor } from '../../../../../../resources/js/components/RichTextEditor';

interface ParentOption {
    id: number;
    name: string;
    [key: string]: string | number;
}

interface CreateProps {
    parent_options: ParentOption[];
    store_url: string;
    cancel_url: string;
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

function toSlug(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function GradientCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="ap-box ap-border-gradient flex flex-col gap-4 p-6">
            <h2 className="font-display text-h6">{title}</h2>
            {children}
        </section>
    );
}

export default function PagesCreate({ parent_options, store_url, cancel_url }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm<PageForm>({
        title: '',
        slug: '',
        content: '',
        meta_description: '',
        parent: '',
        menu_order: '0',
        icon: '',
    });

    useEffect(() => {
        setData('slug', toSlug(data.title));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.title]);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(store_url);
    };

    return (
        <AdminLayout title="Add Page">
            <Head title="Add Page" />

            <form
                onSubmit={submit}
                className="mx-auto flex w-full max-w-6xl flex-col gap-6"
                noValidate
            >
                <div className="flex flex-col gap-6 md:flex-row">
                    <div className="flex flex-1 flex-col gap-6 md:flex-[2]">
                        <GradientCard title="Page">
                            <Input
                                id="title"
                                label="Title"
                                required
                                autoFocus
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

                            <div className="flex items-center gap-3 pt-2">
                                <Link href={cancel_url} className="btn btn-ghost">
                                    Cancel
                                </Link>
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
                                onChange={(event) =>
                                    setData('meta_description', event.target.value)
                                }
                                error={errors.meta_description}
                            />

                            <div className="flex justify-end pt-2">
                                <Button type="submit" color="primary" loading={processing}>
                                    Publish Page
                                </Button>
                            </div>
                        </GradientCard>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
