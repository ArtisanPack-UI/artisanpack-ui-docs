import { Link, usePage } from '@inertiajs/react';
import { ThemeToggle } from '@artisanpack-ui/react';
import type { ReactNode } from 'react';

export interface AdminNavItem {
    label: string;
    href: string;
}

export interface AdminLayoutProps {
    children: ReactNode;
    title?: string;
    nav?: AdminNavItem[];
}

const DEFAULT_NAV: AdminNavItem[] = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Packages', href: '/dashboard/packages' },
    { label: 'Pages', href: '/dashboard/pages' },
    { label: 'Settings', href: '/dashboard/settings' },
];

export function AdminLayout({ children, title, nav = DEFAULT_NAV }: AdminLayoutProps) {
    const { url } = usePage();

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
                            {nav.map((item) => {
                                const active = url === item.href || url.startsWith(`${item.href}/`);
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
                <header
                    className="sticky top-0 z-30 border-b border-border-subtle backdrop-blur-[14px]"
                    style={{ backgroundColor: 'rgba(8, 12, 22, 0.9)' }}
                >
                    <div className="flex items-center justify-between gap-4 px-8 py-4">
                        <h1 className="font-display text-h5">{title}</h1>
                        <ThemeToggle />
                    </div>
                </header>
                <main className="flex-1 px-8 py-8">{children}</main>
            </div>
        </div>
    );
}

export default AdminLayout;
