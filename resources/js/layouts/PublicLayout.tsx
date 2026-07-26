import { Link } from '@inertiajs/react';
import { ThemeToggle } from '@artisanpack-ui/react';
import type { ReactNode } from 'react';

import { PrivacyBanners } from '../components/PrivacyBanners';
import { Seo } from '../components/Seo';

export interface PublicLayoutProps {
    children: ReactNode;
    activeNav?: string;
}

export function PublicLayout({ children, activeNav }: PublicLayoutProps) {
    return (
        <>
            <Seo />
            <div className="min-h-screen flex flex-col bg-base text-text">
                <header className="sticky top-0 z-40 border-b border-border-subtle bg-base/90 backdrop-blur-[14px]">
                    <div className="mx-auto flex w-full max-w-[1440px] items-center justify-between gap-6 px-6 py-4">
                        <Link
                            href="/"
                            className="font-display text-lg font-semibold tracking-tight"
                        >
                            ArtisanPack UI
                        </Link>
                        <nav
                            className="hidden items-center gap-6 text-small md:flex"
                            aria-label="Primary"
                        >
                            <Link
                                href="/documentation"
                                className={`hover:text-secondary ${activeNav === 'docs' ? 'text-secondary' : 'text-text-muted'}`}
                            >
                                Docs
                            </Link>
                            <Link
                                href="/changelogs"
                                className={`hover:text-secondary ${activeNav === 'changelogs' ? 'text-secondary' : 'text-text-muted'}`}
                            >
                                Changelogs
                            </Link>
                        </nav>
                        <div className="flex items-center gap-3">
                            <ThemeToggle />
                        </div>
                    </div>
                    <div
                        className="h-[2px] w-full"
                        style={{ backgroundImage: 'var(--grad-neon)' }}
                        aria-hidden
                    />
                </header>

                <main className="flex-1">{children}</main>

                <PrivacyBanners />

                <footer className="border-t border-border-subtle bg-surface-2">
                    <div className="mx-auto flex w-full max-w-[1440px] flex-col items-center justify-between gap-4 px-6 py-8 text-small text-text-muted md:flex-row">
                        <p>© {new Date().getFullYear()} ArtisanPack UI</p>
                        <div className="flex items-center gap-4">
                            <Link
                                href="/policy"
                                className="cursor-pointer transition hover:text-secondary"
                            >
                                Privacy
                            </Link>
                            <a
                                href="https://github.com/ArtisanPack-UI"
                                className="hover:text-secondary"
                            >
                                GitHub
                            </a>
                            <a href="https://artisanpackui.dev" className="hover:text-secondary">
                                artisanpackui.dev
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

export default PublicLayout;
