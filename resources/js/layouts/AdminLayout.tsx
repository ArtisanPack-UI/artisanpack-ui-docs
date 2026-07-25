import { Link, router, usePage } from '@inertiajs/react';
import { ThemeToggle } from '@artisanpack-ui/react';
import { Dropdown, DropdownItem } from '@artisanpack-ui/react/layout';
import type { ReactNode } from 'react';

import type { UserRole } from '../types/inertia';

export interface AdminNavItem {
    label: string;
    href: string;
    /** URL prefix used for active-state matching. Defaults to `href`. */
    matchPrefix?: string;
    /** If set, only users with one of these roles see this item. */
    roles?: UserRole[];
}

export interface AdminLayoutProps {
    children: ReactNode;
    title?: string;
    nav?: AdminNavItem[];
}

const DEFAULT_NAV: AdminNavItem[] = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Analytics', href: '/dashboard/analytics', matchPrefix: '/dashboard/analytics', roles: ['admin'] },
    { label: 'Packages', href: '/dashboard/packages' },
    { label: 'Pages', href: '/dashboard/pages' },
    { label: 'Users', href: '/dashboard/users', roles: ['admin'] },
    { label: 'Integrations', href: '/dashboard/integrations/google', matchPrefix: '/dashboard/integrations', roles: ['admin'] },
    { label: 'Privacy', href: '/dashboard/privacy', matchPrefix: '/dashboard/privacy', roles: ['admin'] },
    { label: 'Settings', href: '/dashboard/settings', matchPrefix: '/dashboard/settings', roles: ['admin'] },
];

const ACCOUNT_LINKS: { label: string; href: string }[] = [
    { label: 'Profile', href: '/dashboard/settings/profile' },
    { label: 'Password', href: '/dashboard/settings/password' },
    { label: 'Appearance', href: '/dashboard/settings/appearance' },
    { label: 'Two-Factor Auth', href: '/dashboard/settings/two-factor' },
    { label: 'API Tokens', href: '/dashboard/settings/api-tokens' },
];

function AccountMenu({ name }: { name: string }) {
    const initials = name
        .split(' ')
        .map((part) => part.charAt(0).toUpperCase())
        .slice(0, 2)
        .join('') || '?';

    return (
        <Dropdown
            end
            label={name}
            trigger={
                <button
                    type="button"
                    className="flex items-center gap-2 rounded-box px-2 py-1 text-small text-text-muted transition hover:bg-surface hover:text-text"
                    aria-label="Account menu"
                >
                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-surface font-semibold text-text">
                        {initials}
                    </span>
                    <span className="hidden md:inline">{name}</span>
                </button>
            }
        >
            {ACCOUNT_LINKS.map((link) => (
                <DropdownItem key={link.href} onClick={() => router.visit(link.href)}>
                    {link.label}
                </DropdownItem>
            ))}
            <DropdownItem onClick={() => router.post('/logout')}>Sign Out</DropdownItem>
        </Dropdown>
    );
}

export function AdminLayout({ children, title, nav = DEFAULT_NAV }: AdminLayoutProps) {
    const { url, props } = usePage();
    const role = props.auth?.user?.role ?? null;
    const userName = props.auth?.user?.name ?? null;
    const visibleNav = nav.filter((item) => !item.roles || (role && item.roles.includes(role)));

    // Longest-prefix wins so /dashboard/packages highlights Packages, not both Packages and Dashboard.
    const activeHref = visibleNav
        .filter((item) => {
            const prefix = item.matchPrefix ?? item.href;
            return url === prefix || url.startsWith(`${prefix}/`);
        })
        .sort((a, b) => (b.matchPrefix ?? b.href).length - (a.matchPrefix ?? a.href).length)[0]?.href;

    return (
        <div className="min-h-screen bg-base text-text" style={{ display: 'grid', gridTemplateColumns: '260px 1fr' }}>
            <aside className="border-r border-border-subtle bg-surface-2" aria-label="Admin navigation">
                <div className="sticky top-0 flex h-screen flex-col">
                    <div className="border-b border-border-subtle px-6 py-4">
                        <Link href="/dashboard" className="font-display text-lg font-semibold tracking-tight">
                            ArtisanPack UI
                        </Link>
                        <p className="mt-1 text-xsmall uppercase tracking-wider text-text-subtle">Admin</p>
                    </div>
                    <nav className="flex-1 overflow-y-auto px-3 py-4">
                        <ul className="flex flex-col gap-1">
                            {visibleNav.map((item) => {
                                const active = item.href === activeHref;
                                return (
                                    <li key={item.href}>
                                        <Link
                                            href={item.href}
                                            className={`flex items-center gap-3 rounded-box px-3 py-2 text-small transition ${
                                                active
                                                    ? 'bg-surface text-secondary'
                                                    : 'text-text-muted hover:bg-surface hover:text-text'
                                            }`}
                                        >
                                            <span>{item.label}</span>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>
                </div>
            </aside>

            <div className="flex min-w-0 flex-col">
                <header className="sticky top-0 z-30 border-b border-border-subtle bg-base/90 backdrop-blur-[14px]">
                    <div className="flex items-center justify-between gap-4 px-8 py-4">
                        {title ? <h1 className="font-display text-h5">{title}</h1> : <span />}
                        <div className="flex items-center gap-2">
                            <ThemeToggle />
                            {userName ? <AccountMenu name={userName} /> : null}
                        </div>
                    </div>
                </header>
                <main className="flex-1 px-8 py-8">{children}</main>
            </div>
        </div>
    );
}

export default AdminLayout;
