import { Head, Link, useForm } from '@inertiajs/react';
import { Button, Input, Select } from '@artisanpack-ui/react/form';
import { useEffect, type FormEventHandler } from 'react';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';

interface CreateProps {
    store_url: string;
    cancel_url: string;
}

interface PackageForm {
    name: string;
    slug: string;
    wiki_url: string;
    docs_url: string;
    changelog_url: string;
    icon: string;
    version: string;
    package_registry: string;
    [key: string]: string;
}

const REGISTRY_OPTIONS = [
    { id: 'packagist', name: 'Packagist (Composer)' },
    { id: 'npm', name: 'NPM (JavaScript)' },
];

function toSlug(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * Matches the Edit page's flat/gradient-bordered treatment so Create/Edit
 * share the same visual language.
 */
function GradientCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="ap-box ap-border-gradient flex flex-col gap-4 p-6">
            <h2 className="font-display text-h6">{title}</h2>
            {children}
        </section>
    );
}

export default function PackagesCreate({ store_url, cancel_url }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm<PackageForm>({
        name: '',
        slug: '',
        wiki_url: '',
        docs_url: '',
        changelog_url: '',
        icon: '',
        version: '',
        package_registry: '',
    });

    useEffect(() => {
        setData('slug', toSlug(data.name));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.name]);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(store_url);
    };

    return (
        <AdminLayout title="Add Package">
            <Head title="Add Package" />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-6xl flex-col gap-6" noValidate>
                <div className="flex flex-col gap-6 md:flex-row">
                    <div className="flex flex-1 flex-col gap-6 md:flex-[2]">
                        <GradientCard title="Package">
                            <Input
                                id="name"
                                label="Name"
                                required
                                autoFocus
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                error={errors.name}
                            />
                            <Input
                                id="slug"
                                label="Slug"
                                required
                                value={data.slug}
                                onChange={(event) => setData('slug', event.target.value)}
                                error={errors.slug}
                            />
                            <Input
                                id="wiki_url"
                                type="url"
                                label="Wiki URL"
                                hint="GitHub or GitLab wiki URL (e.g. https://github.com/owner/repo/wiki)."
                                value={data.wiki_url}
                                onChange={(event) => setData('wiki_url', event.target.value)}
                                error={errors.wiki_url}
                            />
                            <Input
                                id="docs_url"
                                type="url"
                                label="Docs URL"
                                hint="GitHub repository or docs directory URL. Takes priority over the wiki URL."
                                value={data.docs_url}
                                onChange={(event) => setData('docs_url', event.target.value)}
                                error={errors.docs_url}
                            />
                            <Input
                                id="changelog_url"
                                type="url"
                                label="Changelog URL"
                                required
                                hint="GitHub or GitLab file URL for the CHANGELOG."
                                value={data.changelog_url}
                                onChange={(event) => setData('changelog_url', event.target.value)}
                                error={errors.changelog_url}
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
                            <Input
                                id="icon"
                                label="Icon"
                                value={data.icon}
                                onChange={(event) => setData('icon', event.target.value)}
                                error={errors.icon}
                            />
                            <Input
                                id="version"
                                label="Version"
                                value={data.version}
                                onChange={(event) => setData('version', event.target.value)}
                                error={errors.version}
                            />
                            <Select
                                id="package_registry"
                                label="Package Registry"
                                placeholder="Select registry type"
                                options={REGISTRY_OPTIONS}
                                optionValue="id"
                                optionLabel="name"
                                hint="Package name is auto-generated from slug."
                                value={data.package_registry}
                                onChange={(event) => setData('package_registry', event.target.value)}
                                error={errors.package_registry}
                            />

                            <div className="flex justify-end pt-2">
                                <Button type="submit" color="primary" loading={processing}>
                                    Publish Package
                                </Button>
                            </div>
                        </GradientCard>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
