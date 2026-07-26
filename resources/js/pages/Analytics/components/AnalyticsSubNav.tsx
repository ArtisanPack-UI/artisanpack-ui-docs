import { Link, router, usePage } from '@inertiajs/react';

import type { SharedProps } from '../../../types/inertia';

interface AnalyticsSubNavItem {
    label: string;
    href: string;
}

const ITEMS: AnalyticsSubNavItem[] = [
    { label: 'Overview', href: '/dashboard/analytics' },
    { label: 'Pages', href: '/dashboard/analytics/pages' },
    { label: 'Traffic', href: '/dashboard/analytics/traffic' },
    { label: 'Audience', href: '/dashboard/analytics/audience' },
    { label: 'Events', href: '/dashboard/analytics/events' },
    { label: 'Realtime', href: '/dashboard/analytics/realtime' },
];

export function AnalyticsSubNav() {
    const page = usePage<SharedProps>();
    const currentPath = page.url.split('?')[0];
    const analytics = page.props.analyticsSource;

    // Longest-prefix match so a nested route (e.g. .../pages) doesn't
    // also highlight the shorter Overview link (which matches every
    // /dashboard/analytics/* url as a prefix).
    const active = [...ITEMS]
        .sort((a, b) => b.href.length - a.href.length)
        .find((item) => currentPath === item.href);

    const handleSourceChange = (event: React.ChangeEvent<HTMLSelectElement>): void => {
        if (!analytics) {
            return;
        }

        router.post(analytics.update_url, { source: event.target.value }, { preserveScroll: true });
    };

    return (
        <div className="flex flex-col gap-2">
            <nav
                aria-label="Analytics sections"
                className="relative border-b border-transparent"
                style={{
                    // Gradient hairline directly under the tab row, using
                    // the same `--grad-neon` token that powers the Docs
                    // layout header underline. Rendered as a background
                    // image on a 1px-tall pseudo-track so it stays crisp
                    // at every DPR without needing a real border.
                    backgroundImage:
                        'linear-gradient(to right, var(--color-border-subtle), var(--color-border-subtle)), var(--grad-neon)',
                    backgroundSize: '100% 1px, 100% 1px',
                    backgroundPosition: 'bottom left, bottom left',
                    backgroundRepeat: 'no-repeat, no-repeat',
                }}
            >
                <div className="flex flex-wrap items-end justify-between gap-4 px-1 pb-3">
                    <ul className="flex flex-wrap gap-6">
                        {ITEMS.map((item) => {
                            const isActive = active?.href === item.href;
                            return (
                                <li key={item.href}>
                                    <Link
                                        href={item.href}
                                        className={`inline-flex items-center border-b-2 pb-1 text-small font-medium transition ${
                                            isActive
                                                ? 'border-secondary text-text'
                                                : 'border-transparent text-text-muted hover:border-border hover:text-text'
                                        }`}
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                    {analytics ? (
                        <label className="flex items-center gap-2 text-small text-text-muted">
                            <span>Source</span>
                            <select
                                value={analytics.active}
                                onChange={handleSourceChange}
                                className="rounded-[8px] border border-border-subtle bg-surface px-3 py-1.5 text-small text-text focus:border-secondary focus:outline-none"
                            >
                                {Object.entries(analytics.options).map(([id, label]) => (
                                    <option
                                        key={id}
                                        value={id}
                                        disabled={id === 'google' && !analytics.google_available}
                                    >
                                        {label}
                                        {id === 'google' && !analytics.google_available
                                            ? ' (connect account)'
                                            : ''}
                                    </option>
                                ))}
                            </select>
                        </label>
                    ) : null}
                </div>
            </nav>
            {analytics && analytics.active === 'google' && !analytics.google_available ? (
                <p className="px-1 text-xsmall text-text-subtle">
                    Google is selected but no connected Google account is available. Connect one at{' '}
                    <Link href="/dashboard/integrations/google" className="underline">
                        Integrations → Google
                    </Link>{' '}
                    or switch back to Local.
                </p>
            ) : null}
        </div>
    );
}
