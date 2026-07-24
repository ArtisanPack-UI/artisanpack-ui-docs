import { Head, useForm } from '@inertiajs/react';
import { Button, Input, Select } from '@artisanpack-ui/react/form';
import { Alert } from '@artisanpack-ui/react/feedback';
import type { FormEventHandler } from 'react';

import { AdminLayout } from '../../../../../../resources/js/layouts/AdminLayout';

interface PageOption {
    id: number;
    title: string;
}

interface SettingsPayload {
    home_page: number | null;
    google_analytics_id: string;
    has_github_token: boolean;
    has_gitlab_token: boolean;
}

interface SettingsProps {
    settings: SettingsPayload;
    pages: PageOption[];
    update_url: string;
    flash?: { success?: string | null };
}

interface SettingsForm {
    home_page: string;
    google_analytics_id: string;
    github_token: string;
    gitlab_token: string;
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

export default function SiteSettings({ settings, pages, update_url, flash }: SettingsProps) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm<SettingsForm>({
        home_page: settings.home_page !== null ? String(settings.home_page) : '',
        google_analytics_id: settings.google_analytics_id,
        github_token: '',
        gitlab_token: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(update_url, {
            preserveScroll: true,
            onSuccess: () => {
                setData('github_token', '');
                setData('gitlab_token', '');
            },
        });
    };

    const pageOptions = [{ id: '', title: 'Select a page' }, ...pages.map((page) => ({ id: String(page.id), title: page.title }))];

    return (
        <AdminLayout title="Settings">
            <Head title="Settings" />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-4xl flex-col gap-6" noValidate>
                {flash?.success ? <Alert color="success">{flash.success}</Alert> : null}
                {recentlySuccessful && !flash?.success ? (
                    <Alert color="success">Settings saved successfully.</Alert>
                ) : null}

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <GradientCard title="General">
                        <Select
                            id="home_page"
                            label="Home Page"
                            options={pageOptions}
                            optionValue="id"
                            optionLabel="title"
                            value={data.home_page}
                            onChange={(event) => setData('home_page', event.target.value)}
                            error={errors.home_page}
                        />
                        <Input
                            id="google_analytics_id"
                            label="Google Analytics ID"
                            placeholder="G-XXXXXXXXXX"
                            hint="Your GA4 Measurement ID (e.g., G-ABC123XYZ)."
                            value={data.google_analytics_id}
                            onChange={(event) => setData('google_analytics_id', event.target.value)}
                            error={errors.google_analytics_id}
                        />
                    </GradientCard>

                    <GradientCard title="Integrations">
                        <Input
                            id="gitlab_token"
                            type="password"
                            label="GitLab Token"
                            placeholder={settings.has_gitlab_token ? '••••••••••••' : ''}
                            hint={
                                settings.has_gitlab_token
                                    ? 'Token configured. Enter a new value to replace it.'
                                    : 'A GitLab personal access token with the read_api and read_repository scopes.'
                            }
                            value={data.gitlab_token}
                            onChange={(event) => setData('gitlab_token', event.target.value)}
                            error={errors.gitlab_token}
                            autoComplete="new-password"
                        />
                        <Input
                            id="github_token"
                            type="password"
                            label="GitHub Token"
                            placeholder={settings.has_github_token ? '••••••••••••' : ''}
                            hint={
                                settings.has_github_token
                                    ? 'Token configured. Enter a new value to replace it.'
                                    : 'A GitHub personal access token (PAT) with the repo scope.'
                            }
                            value={data.github_token}
                            onChange={(event) => setData('github_token', event.target.value)}
                            error={errors.github_token}
                            autoComplete="new-password"
                        />
                    </GradientCard>
                </div>

                <div className="flex justify-end">
                    <Button type="submit" color="primary" loading={processing}>
                        Save Settings
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
