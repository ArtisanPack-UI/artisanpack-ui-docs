import { Head, useForm, router } from '@inertiajs/react';
import { Button, Checkbox, Input } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Alert } from '@artisanpack-ui/react/feedback';
import { type FormEventHandler } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';

interface TokenSummary {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    created_at: string | null;
    needs_rotation: boolean;
    age_days: number | null;
}

interface AbilityOption {
    value: string;
    label: string;
}

interface NewToken {
    name: string;
    plain_text: string;
}

interface ApiTokensProps {
    tokens: TokenSummary[];
    availableAbilities: AbilityOption[];
    newToken: NewToken | null;
    status: string | null;
    rotationAgeDays: number;
}

type CreateForm = {
    name: string;
    abilities: string[];
};

const STATUS_MESSAGES: Record<string, string> = {
    'api-token-created': 'Token generated. Copy it now — it will not be shown again.',
    'api-token-revoked': 'Token revoked.',
    'api-token-rotated': 'Token rotated. Copy the new value now — it will not be shown again.',
};

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

export default function ApiTokens({
    tokens,
    availableAbilities,
    newToken,
    status,
    rotationAgeDays,
}: ApiTokensProps) {
    const { data, setData, post, processing, errors, reset } = useForm<CreateForm>({
        name: '',
        abilities: [],
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post('/dashboard/settings/api-tokens', {
            preserveScroll: true,
            onSuccess: () => reset('name', 'abilities'),
        });
    };

    const toggleAbility = (value: string, checked: boolean) => {
        if (checked) {
            setData('abilities', Array.from(new Set([...data.abilities, value])));
        } else {
            setData(
                'abilities',
                data.abilities.filter((ability) => ability !== value),
            );
        }
    };

    const revoke = (token: TokenSummary) => {
        if (!window.confirm(`Revoke the "${token.name}" token? This cannot be undone.`)) {
            return;
        }

        router.delete(`/dashboard/settings/api-tokens/${token.id}`, {
            preserveScroll: true,
        });
    };

    const rotate = (token: TokenSummary) => {
        if (
            !window.confirm(
                `Rotate the "${token.name}" token? The old token will be revoked and a new one issued with the same abilities.`,
            )
        ) {
            return;
        }

        router.post(
            `/dashboard/settings/api-tokens/${token.id}/rotate`,
            {},
            { preserveScroll: true },
        );
    };

    const staleCount = tokens.filter((token) => token.needs_rotation).length;

    return (
        <AdminLayout title="API tokens">
            <Head title="API tokens" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-8">
                <header>
                    <p className="text-small text-text-muted">
                        Personal access tokens let external clients — like{' '}
                        <code>artisanpackui.dev</code> — call this site's API on your behalf. Grant
                        only the abilities the client needs and rotate tokens every 90 days.
                    </p>
                </header>

                {status && STATUS_MESSAGES[status] ? (
                    <Alert color="success">{STATUS_MESSAGES[status]}</Alert>
                ) : null}

                {staleCount > 0 ? (
                    <Alert color="warning" data-testid="stale-token-warning">
                        {staleCount === 1
                            ? `1 token is older than ${rotationAgeDays} days. Rotate it to stay within policy.`
                            : `${staleCount} tokens are older than ${rotationAgeDays} days. Rotate them to stay within policy.`}
                    </Alert>
                ) : null}

                {newToken ? (
                    <Alert color="warning">
                        <div className="flex flex-col gap-2">
                            <p className="font-semibold">New token for "{newToken.name}"</p>
                            <p className="text-small">
                                Copy this token now — it will not be shown again.
                            </p>
                            <code
                                className="block break-all rounded-box bg-surface p-3 font-mono text-small"
                                data-testid="new-api-token"
                            >
                                {newToken.plain_text}
                            </code>
                        </div>
                    </Alert>
                ) : null}

                <Card>
                    <form onSubmit={submit} className="flex flex-col gap-6" noValidate>
                        <h2 className="text-medium font-semibold">Issue a new token</h2>

                        <Input
                            id="name"
                            name="name"
                            type="text"
                            label="Token name"
                            placeholder="e.g. artisanpackui.dev production"
                            required
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            error={errors.name}
                        />

                        <fieldset className="flex flex-col gap-3">
                            <legend className="text-small font-semibold text-text">
                                Abilities
                            </legend>
                            <p className="text-small text-text-muted">
                                Choose which resources this token can read or write.
                            </p>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                {availableAbilities.map((ability) => (
                                    <Checkbox
                                        key={ability.value}
                                        id={`ability-${ability.value}`}
                                        name="abilities[]"
                                        label={ability.label}
                                        checked={data.abilities.includes(ability.value)}
                                        onChange={(event) =>
                                            toggleAbility(ability.value, event.target.checked)
                                        }
                                    />
                                ))}
                            </div>
                            {errors.abilities ? (
                                <p className="text-small text-danger">{errors.abilities}</p>
                            ) : null}
                        </fieldset>

                        <div>
                            <Button type="submit" color="primary" loading={processing}>
                                Generate token
                            </Button>
                        </div>
                    </form>
                </Card>

                <Card>
                    <div className="flex flex-col gap-4">
                        <h2 className="text-medium font-semibold">Active tokens</h2>

                        {tokens.length === 0 ? (
                            <p className="text-small text-text-muted">
                                You have not issued any tokens yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-small">
                                    <thead className="text-left text-text-muted">
                                        <tr>
                                            <th className="py-2 pr-4 font-semibold">Name</th>
                                            <th className="py-2 pr-4 font-semibold">Abilities</th>
                                            <th className="py-2 pr-4 font-semibold">Last used</th>
                                            <th className="py-2 pr-4 font-semibold">Created</th>
                                            <th className="py-2 font-semibold">
                                                <span className="sr-only">Actions</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {tokens.map((token) => (
                                            <tr
                                                key={token.id}
                                                className="border-t border-border"
                                                data-testid={`token-row-${token.id}`}
                                                data-needs-rotation={
                                                    token.needs_rotation ? 'true' : 'false'
                                                }
                                            >
                                                <td className="py-3 pr-4 font-medium text-text">
                                                    <div className="flex items-center gap-2">
                                                        <span>{token.name}</span>
                                                        {token.needs_rotation ? (
                                                            <span
                                                                className="rounded-box bg-warning/15 px-2 py-0.5 text-xsmall font-semibold text-warning"
                                                                data-testid="rotation-badge"
                                                                title={
                                                                    token.age_days !== null
                                                                        ? `Token is ${token.age_days} days old`
                                                                        : undefined
                                                                }
                                                            >
                                                                Rotate
                                                            </span>
                                                        ) : null}
                                                    </div>
                                                </td>
                                                <td className="py-3 pr-4 text-text-muted">
                                                    {token.abilities.length > 0
                                                        ? token.abilities.join(', ')
                                                        : '—'}
                                                </td>
                                                <td className="py-3 pr-4 text-text-muted">
                                                    {formatDate(token.last_used_at)}
                                                </td>
                                                <td className="py-3 pr-4 text-text-muted">
                                                    {formatDate(token.created_at)}
                                                </td>
                                                <td className="py-3 text-right">
                                                    <div className="flex justify-end gap-2">
                                                        {token.needs_rotation ? (
                                                            <Button
                                                                type="button"
                                                                color="warning"
                                                                onClick={() => rotate(token)}
                                                            >
                                                                Rotate
                                                            </Button>
                                                        ) : null}
                                                        <Button
                                                            type="button"
                                                            color="danger"
                                                            onClick={() => revoke(token)}
                                                        >
                                                            Revoke
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}
