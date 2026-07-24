import { usePage } from '@inertiajs/react';
import { useConsent } from '@artisanpack-ui/privacy/react';
import { useMemo, useState, type ChangeEvent } from 'react';

import { csrfToken } from '@/lib/csrf';
import type { SharedProps } from '@/types/inertia';

export function PrivacyBanners() {
    return (
        <>
            <CookieBanner />
            <PolicyReconsentBanner />
        </>
    );
}

function CookieBanner() {
    const { state, loading, setConsents } = useConsent();
    const [showPreferences, setShowPreferences] = useState(false);
    const [selected, setSelected] = useState<Record<string, boolean>>({});
    const [saving, setSaving] = useState(false);
    const [dismissed, setDismissed] = useState(false);

    const requiredKeys = useMemo(() => {
        if (!state?.categories) {
            return [] as string[];
        }
        return Object.entries(state.categories)
            .filter(([, config]) => config.required === true)
            .map(([key]) => key);
    }, [state?.categories]);

    if (loading || state === null || dismissed) {
        return null;
    }

    // Same heuristic as the package: hide once the visitor has made a
    // meaningful choice for at least one non-required category.
    const alreadyChose = Object.entries(state.consents).some(
        ([key, value]) => !requiredKeys.includes(key) && value === true,
    );
    if (alreadyChose) {
        return null;
    }

    const categoryEntries = Object.entries(state.categories);

    const buildAll = (granted: boolean): Record<string, boolean> => {
        const map: Record<string, boolean> = {};
        categoryEntries.forEach(([key, config]) => {
            map[key] = granted || config.required === true;
        });
        return map;
    };

    const submit = async (consents: Record<string, boolean>) => {
        setSaving(true);
        try {
            await setConsents(consents);
            setDismissed(true);
        } finally {
            setSaving(false);
        }
    };

    const openPreferences = () => {
        setSelected(
            Object.fromEntries(
                categoryEntries.map(([key, config]) => [
                    key,
                    config.required === true || state.consents[key] === true,
                ]),
            ),
        );
        setShowPreferences(true);
    };

    const togglePreference =
        (key: string) =>
        (event: ChangeEvent<HTMLInputElement>): void => {
            if (requiredKeys.includes(key)) {
                return;
            }
            setSelected((prev) => ({ ...prev, [key]: event.target.checked }));
        };

    return (
        <div
            role="dialog"
            aria-modal="false"
            aria-label="Cookie consent"
            className="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-[720px] flex-col gap-4 rounded-[14px] border border-border-subtle bg-surface-2/95 p-6 text-text shadow-[0_20px_50px_-12px_rgba(0,0,0,0.6)] backdrop-blur-[14px] md:inset-x-auto md:right-6 md:bottom-6 md:left-auto md:w-[440px]"
        >
            <div>
                <h2 className="font-display text-lg font-semibold tracking-tight">
                    We value your privacy
                </h2>
                <p className="mt-2 text-small text-text-muted">
                    We use cookies to make this site work and to understand how it's used. Choose
                    which categories you allow — you can change this later from any page.
                </p>
            </div>

            {showPreferences ? (
                <ul
                    className="flex flex-col gap-3 border-t border-border-subtle pt-4"
                    role="group"
                    aria-label="Cookie categories"
                >
                    {categoryEntries.map(([key, config]) => {
                        const required = config.required === true;
                        const inputId = `privacy-cookie-${key}`;
                        return (
                            <li key={key} className="flex items-start justify-between gap-4">
                                <label htmlFor={inputId} className="flex-1 text-small">
                                    <span className="font-medium">
                                        {config.name ?? key}
                                        {required ? (
                                            <span className="ml-1 text-text-subtle">
                                                (required)
                                            </span>
                                        ) : null}
                                    </span>
                                    {config.description ? (
                                        <span className="mt-1 block text-text-muted">
                                            {config.description}
                                        </span>
                                    ) : null}
                                </label>
                                <input
                                    id={inputId}
                                    type="checkbox"
                                    className="mt-1 h-4 w-4 cursor-pointer accent-primary disabled:cursor-not-allowed"
                                    checked={selected[key] ?? required}
                                    disabled={required || saving}
                                    onChange={togglePreference(key)}
                                />
                            </li>
                        );
                    })}
                </ul>
            ) : null}

            <div className="flex flex-wrap justify-end gap-2">
                {showPreferences ? (
                    <button
                        type="button"
                        disabled={saving}
                        onClick={() => void submit(selected)}
                        className="inline-flex h-9 items-center rounded-[8px] bg-primary px-4 text-small font-medium text-base transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Save selection
                    </button>
                ) : (
                    <>
                        <button
                            type="button"
                            disabled={saving}
                            onClick={openPreferences}
                            className="inline-flex h-9 items-center rounded-[8px] border border-border-subtle bg-transparent px-4 text-small font-medium text-text-muted transition hover:text-text disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Customize
                        </button>
                        <button
                            type="button"
                            disabled={saving}
                            onClick={() => void submit(buildAll(false))}
                            className="inline-flex h-9 items-center rounded-[8px] border border-border-subtle bg-surface px-4 text-small font-medium text-text transition hover:bg-surface/80 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Reject all
                        </button>
                        <button
                            type="button"
                            disabled={saving}
                            onClick={() => void submit(buildAll(true))}
                            className="inline-flex h-9 items-center rounded-[8px] bg-primary px-4 text-small font-medium text-base transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Accept all
                        </button>
                    </>
                )}
            </div>
        </div>
    );
}

function PolicyReconsentBanner() {
    const { reconsent } = usePage<SharedProps>().props;
    const [dismissed, setDismissed] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Guard against `undefined` (partial reload didn't include the prop)
    // as well as `null` (no active policy or the user is up to date).
    if (!reconsent || dismissed) {
        return null;
    }

    const accept = async () => {
        setSaving(true);
        setError(null);
        try {
            const response = await fetch('/reconsent', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    version: reconsent.version,
                    regulation: reconsent.regulation,
                }),
            });
            if (response.ok) {
                setDismissed(true);
                return;
            }
            if (response.status === 401) {
                setError('You need to sign in to accept the updated policy.');
                return;
            }
            if (response.status === 409) {
                setError(
                    'The policy has changed again. Reload the page to see the latest version.',
                );
                return;
            }
            setError('We could not record your consent. Please try again.');
        } catch {
            setError('Network error — please check your connection and try again.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <div
            role="dialog"
            aria-modal="false"
            aria-label="Privacy policy updated"
            className="fixed inset-x-4 top-4 z-50 mx-auto flex max-w-[720px] flex-col gap-3 rounded-[12px] border border-primary/40 bg-surface-2/95 p-5 text-text shadow-[0_20px_50px_-12px_rgba(0,0,0,0.6)] backdrop-blur-[14px] md:inset-x-auto md:top-6 md:right-6 md:left-auto md:w-[440px]"
        >
            <div>
                <h2 className="font-display text-base font-semibold tracking-tight">
                    Our privacy policy has been updated
                </h2>
                <p className="mt-1 text-small text-text-muted">
                    Please review the changes and confirm you accept the current terms.
                </p>
            </div>
            {error ? (
                <p role="alert" className="text-small text-error">
                    {error}
                </p>
            ) : null}
            <div className="flex flex-wrap justify-end gap-2">
                <button
                    type="button"
                    onClick={() => setDismissed(true)}
                    disabled={saving}
                    className="inline-flex h-9 items-center rounded-[8px] border border-border-subtle bg-transparent px-4 text-small font-medium text-text-muted transition hover:text-text disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Later
                </button>
                <a
                    href={reconsent.url}
                    className="inline-flex h-9 items-center rounded-[8px] border border-border-subtle bg-surface px-4 text-small font-medium text-text transition hover:bg-surface/80"
                >
                    Review
                </a>
                <button
                    type="button"
                    onClick={() => void accept()}
                    disabled={saving}
                    className="inline-flex h-9 items-center rounded-[8px] bg-primary px-4 text-small font-medium text-base transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Accept
                </button>
            </div>
        </div>
    );
}
