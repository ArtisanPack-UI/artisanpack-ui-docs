import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button, Input, Select } from '@artisanpack-ui/react/form';
import { Alert } from '@artisanpack-ui/react/feedback';
import type { FormEventHandler } from 'react';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';

interface DocumentationOption {
    id: number;
    name: string;
    [key: string]: string | number;
}

interface PackagePayload {
    id: number;
    name: string;
    slug: string;
    homepage: number | null;
    wiki_url: string;
    docs_url: string;
    changelog_url: string;
    icon: string;
    version: string;
    package_registry: string | null;
}

interface EditProps {
    package: PackagePayload;
    documentation_options: DocumentationOption[];
    update_url: string;
    destroy_url: string;
    index_url: string;
    documentation_url: string;
    can_delete: boolean;
    flash?: { success?: string | null };
}

interface PackageForm {
    name: string;
    slug: string;
    homepage: string;
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

/**
 * Flat card with a gradient border to match the design system's `.ap-box
 * .ap-border-gradient` treatment. Used for both the main form and the sidebar
 * panels so the whole edit page reads as one cohesive surface.
 */
function GradientCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="ap-box ap-border-gradient flex flex-col gap-4 p-6">
            <h2 className="font-display text-h6">{title}</h2>
            {children}
        </section>
    );
}

export default function PackagesEdit({
    package: pkg,
    documentation_options,
    update_url,
    destroy_url,
    index_url,
    documentation_url,
    can_delete,
    flash,
}: EditProps) {
    const { data, setData, patch, processing, errors } = useForm<PackageForm>({
        name: pkg.name,
        slug: pkg.slug,
        homepage: pkg.homepage === null ? '' : String(pkg.homepage),
        wiki_url: pkg.wiki_url,
        docs_url: pkg.docs_url,
        changelog_url: pkg.changelog_url,
        icon: pkg.icon,
        version: pkg.version,
        package_registry: pkg.package_registry ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(update_url, { preserveScroll: true });
    };

    const handleDelete = () => {
        if (!window.confirm(`Delete package "${pkg.name}"?`)) {
            return;
        }
        router.delete(destroy_url);
    };

    return (
        <AdminLayout title="Edit Package">
            <Head title={`Edit ${pkg.name}`} />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-6xl flex-col gap-6" noValidate>
                {flash?.success ? <Alert color="success">{flash.success}</Alert> : null}

                <div className="flex flex-col gap-6 md:flex-row">
                    <div className="flex flex-1 flex-col gap-6 md:flex-[2]">
                        <GradientCard title="Package">
                            <Input
                                id="name"
                                label="Name"
                                required
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
                                hint="GitHub or GitLab wiki URL."
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

                            <div className="flex flex-wrap items-center gap-3 pt-2">
                                <Link href={index_url} className="btn btn-ghost">
                                    Back
                                </Link>
                                {can_delete ? (
                                    <Button
                                        type="button"
                                        color="error"
                                        onClick={handleDelete}
                                        className="ml-auto"
                                    >
                                        Delete Package
                                    </Button>
                                ) : null}
                            </div>
                        </GradientCard>
                    </div>

                    <div className="flex flex-1 flex-col gap-6">
                        <GradientCard title="Page Details">
                            <Select
                                id="homepage"
                                label="Homepage"
                                placeholder="Select a page"
                                options={documentation_options}
                                optionValue="id"
                                optionLabel="name"
                                value={data.homepage}
                                onChange={(event) => setData('homepage', event.target.value)}
                                error={errors.homepage}
                            />
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
                                    Update Package
                                </Button>
                            </div>
                        </GradientCard>

                        <GradientCard title="Documentation">
                            <p className="text-small text-text-muted">
                                Reorder the imported documentation pages. Docs themselves are pulled from GitHub.
                            </p>
                            <div className="flex justify-end">
                                <Link href={documentation_url} className="btn btn-secondary">
                                    Manage Order
                                </Link>
                            </div>
                        </GradientCard>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
