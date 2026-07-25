import { Link, usePage } from '@inertiajs/react';

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
    const { url } = usePage();
    const currentPath = url.split( '?' )[ 0 ];

    // Longest-prefix match so a nested route (e.g. .../pages) doesn't
    // also highlight the shorter Overview link (which matches every
    // /dashboard/analytics/* url as a prefix).
    const active = [ ...ITEMS ]
        .sort( ( a, b ) => b.href.length - a.href.length )
        .find( ( item ) => currentPath === item.href );

    return (
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
            <ul className="flex flex-wrap gap-6 px-1 pb-3">
                {ITEMS.map( ( item ) => {
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
                } )}
            </ul>
        </nav>
    );
}
