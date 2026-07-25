import { Head, router } from '@inertiajs/react';
import { Button, Input, Select } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { useState, type FormEventHandler } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';

interface UserRef {
    id: number;
    name: string;
    email: string;
}

interface AuditEntry {
    id: number;
    actor_name: string;
    user: UserRef | null;
    source: 'web' | 'api';
    resource_type: string;
    resource_label: string;
    resource_id: number | null;
    action: 'created' | 'updated' | 'deleted' | 'reordered';
    changes: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string | null;
}

interface Option {
    value: string;
    label: string;
}

interface AuditLogProps {
    entries: {
        data: AuditEntry[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
        };
        links: {
            prev: string | null;
            next: string | null;
        };
    };
    filters: {
        resource: string | null;
        actor: string | null;
        source: string | null;
        from: string | null;
        to: string | null;
    };
    resourceOptions: Option[];
    sourceOptions: Option[];
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }
    try {
        return new Date(value).toLocaleString();
    } catch {
        return value;
    }
}

const ACTION_COLOR: Record<AuditEntry['action'], string> = {
    created: 'bg-success/15 text-success',
    updated: 'bg-info/15 text-info',
    deleted: 'bg-danger/15 text-danger',
    reordered: 'bg-warning/15 text-warning',
};

export default function AuditLog({ entries, filters, resourceOptions, sourceOptions }: AuditLogProps) {
    const [form, setForm] = useState({
        resource: filters.resource ?? '',
        actor: filters.actor ?? '',
        source: filters.source ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        router.get(
            '/dashboard/audit-log',
            Object.fromEntries(Object.entries(form).filter(([, value]) => value !== '')),
            { preserveScroll: true, preserveState: true },
        );
    };

    const reset = () => {
        setForm({ resource: '', actor: '', source: '', from: '', to: '' });
        router.get('/dashboard/audit-log', {}, { preserveScroll: true });
    };

    return (
        <AdminLayout title="Audit log">
            <Head title="Audit log" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-8">
                <header>
                    <h1 className="text-large font-semibold">Audit log</h1>
                    <p className="text-small text-text-muted">
                        Every write to packages, documentation, and changelogs — from
                        both the admin UI and the v1 API — is recorded here.
                    </p>
                </header>

                <Card>
                    <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                            <Select
                                id="resource"
                                name="resource"
                                label="Resource"
                                value={form.resource}
                                onChange={(event) => setForm({ ...form, resource: event.target.value })}
                            >
                                <option value="">All resources</option>
                                {resourceOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </Select>

                            <Input
                                id="actor"
                                name="actor"
                                type="text"
                                label="Actor"
                                placeholder="Name or token"
                                value={form.actor}
                                onChange={(event) => setForm({ ...form, actor: event.target.value })}
                            />

                            <Select
                                id="source"
                                name="source"
                                label="Source"
                                value={form.source}
                                onChange={(event) => setForm({ ...form, source: event.target.value })}
                            >
                                <option value="">Any</option>
                                {sourceOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </Select>

                            <Input
                                id="from"
                                name="from"
                                type="date"
                                label="From"
                                value={form.from}
                                onChange={(event) => setForm({ ...form, from: event.target.value })}
                            />

                            <Input
                                id="to"
                                name="to"
                                type="date"
                                label="To"
                                value={form.to}
                                onChange={(event) => setForm({ ...form, to: event.target.value })}
                            />
                        </div>

                        <div className="flex gap-2">
                            <Button type="submit" color="primary">
                                Apply filters
                            </Button>
                            <Button type="button" color="secondary" onClick={reset}>
                                Reset
                            </Button>
                        </div>
                    </form>
                </Card>

                <Card>
                    <div className="flex flex-col gap-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-medium font-semibold">
                                {entries.meta.total} {entries.meta.total === 1 ? 'entry' : 'entries'}
                            </h2>
                            <p className="text-small text-text-muted">
                                Page {entries.meta.current_page} of {entries.meta.last_page}
                            </p>
                        </div>

                        {entries.data.length === 0 ? (
                            <p className="text-small text-text-muted">
                                No entries match the current filters.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-small">
                                    <thead className="text-left text-text-muted">
                                        <tr>
                                            <th className="py-2 pr-4 font-semibold">When</th>
                                            <th className="py-2 pr-4 font-semibold">Actor</th>
                                            <th className="py-2 pr-4 font-semibold">Source</th>
                                            <th className="py-2 pr-4 font-semibold">Resource</th>
                                            <th className="py-2 pr-4 font-semibold">Action</th>
                                            <th className="py-2 pr-4 font-semibold">Changes</th>
                                            <th className="py-2 font-semibold">IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {entries.data.map((entry) => (
                                            <tr
                                                key={entry.id}
                                                className="border-t border-border align-top"
                                                data-testid={`audit-row-${entry.id}`}
                                            >
                                                <td className="py-3 pr-4 text-text-muted whitespace-nowrap">
                                                    {formatDate(entry.created_at)}
                                                </td>
                                                <td className="py-3 pr-4 font-medium text-text">
                                                    {entry.actor_name}
                                                    {entry.user ? (
                                                        <div className="text-xsmall text-text-muted">
                                                            {entry.user.email}
                                                        </div>
                                                    ) : null}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <span className="rounded-box bg-surface px-2 py-0.5 text-xsmall uppercase tracking-wide text-text-muted">
                                                        {entry.source}
                                                    </span>
                                                </td>
                                                <td className="py-3 pr-4 text-text-muted">
                                                    <span className="font-medium capitalize text-text">
                                                        {entry.resource_label}
                                                    </span>
                                                    {entry.resource_id !== null ? (
                                                        <span className="ml-1 text-text-muted">
                                                            #{entry.resource_id}
                                                        </span>
                                                    ) : null}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <span
                                                        className={`rounded-box px-2 py-0.5 text-xsmall font-semibold uppercase ${
                                                            ACTION_COLOR[entry.action] ?? 'bg-surface text-text-muted'
                                                        }`}
                                                    >
                                                        {entry.action}
                                                    </span>
                                                </td>
                                                <td className="py-3 pr-4 text-text-muted">
                                                    {entry.changes ? (
                                                        <details>
                                                            <summary className="cursor-pointer text-text hover:text-primary">
                                                                {Object.keys(entry.changes).length} field
                                                                {Object.keys(entry.changes).length === 1 ? '' : 's'}
                                                            </summary>
                                                            <pre className="mt-2 max-h-64 overflow-auto rounded-box bg-surface p-2 text-xsmall">
                                                                {JSON.stringify(entry.changes, null, 2)}
                                                            </pre>
                                                        </details>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="py-3 text-text-muted font-mono text-xsmall">
                                                    {entry.ip_address ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                color="secondary"
                                disabled={!entries.links.prev}
                                onClick={() => entries.links.prev && router.get(entries.links.prev)}
                            >
                                Previous
                            </Button>
                            <Button
                                type="button"
                                color="secondary"
                                disabled={!entries.links.next}
                                onClick={() => entries.links.next && router.get(entries.links.next)}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}
