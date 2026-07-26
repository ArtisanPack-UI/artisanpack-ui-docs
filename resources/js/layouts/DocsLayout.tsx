import { Link } from '@inertiajs/react';
import { ThemeToggle } from '@artisanpack-ui/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';

import { PrivacyBanners } from '../components/PrivacyBanners';
import { SearchOverlay } from '../components/SearchOverlay';
import { Seo } from '../components/Seo';
import { useMediaQuery } from '../hooks/useMediaQuery';
import { useSearchOverlay } from '../hooks/useSearchOverlay';

export interface DocsLayoutProps {
    children: ReactNode;
    sidebar?: ReactNode;
    toc?: ReactNode;
}

export function DocsLayout({ children, sidebar, toc }: DocsLayoutProps) {
    const {
        open: searchOpen,
        openOverlay: openSearch,
        closeOverlay: closeSearch,
    } = useSearchOverlay();
    const rootRef = useRef<HTMLDivElement>(null);
    const headerRef = useRef<HTMLElement>(null);
    const [mobileNavOpen, setMobileNavOpen] = useState(false);
    // Render the TOC in exactly one place at a time. Rendering it in
    // both the mobile-inline slot and the desktop rail (hidden via CSS)
    // would mount two IntersectionObservers on the same #main headings
    // and let their scroll-spy states race.
    const isDesktop = useMediaQuery('(min-width: 1280px)');

    useEffect(() => {
        const root = rootRef.current;
        const header = headerRef.current;
        if (!root || !header || typeof ResizeObserver === 'undefined') {
            return;
        }

        const sync = () => {
            root.style.setProperty('--docs-header-h', `${header.offsetHeight}px`);
        };
        sync();

        const observer = new ResizeObserver(sync);
        observer.observe(header);
        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (!mobileNavOpen) {
            return;
        }
        const originalOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = originalOverflow;
        };
    }, [mobileNavOpen]);

    useEffect(() => {
        if (!mobileNavOpen) {
            return;
        }
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setMobileNavOpen(false);
            }
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [mobileNavOpen]);

    return (
        <>
            <Seo />
            <div
                ref={rootRef}
                className="min-h-screen bg-base text-text"
                style={{
                    background: 'linear-gradient(180deg, var(--color-base) 0%, var(--ap-ink) 100%)',
                }}
            >
                <header
                    ref={headerRef}
                    className="sticky top-0 z-40 bg-base/90 backdrop-blur-[14px]"
                >
                    <div className="flex h-[72px] w-full items-center gap-3 px-4 md:gap-7 md:px-7">
                        {sidebar ? (
                            <button
                                type="button"
                                onClick={() => setMobileNavOpen(true)}
                                className="inline-flex h-[38px] w-[38px] items-center justify-center rounded-[9px] border border-border-subtle bg-surface-2 text-text-muted transition hover:text-text xl:hidden"
                                aria-label="Open navigation"
                                aria-expanded={mobileNavOpen}
                                aria-controls="docs-mobile-nav"
                            >
                                <i className="fa-solid fa-bars text-[16px]" aria-hidden />
                            </button>
                        ) : null}

                        <Link
                            href="/"
                            className="flex flex-shrink-0 items-center"
                            aria-label="ArtisanPack UI home"
                        >
                            <img
                                src="/images/artisanpack-ui-wordmark-light-color@3x.png"
                                alt="ArtisanPack UI"
                                className="hidden h-9 w-auto dark:block"
                            />
                            <img
                                src="/images/artisanpack-ui-wordmark-dark-color@3x.png"
                                alt="ArtisanPack UI"
                                className="block h-9 w-auto dark:hidden"
                            />
                        </Link>

                        <button
                            type="button"
                            onClick={openSearch}
                            className="hidden h-[42px] flex-1 items-center gap-2.5 rounded-[10px] border border-border-subtle bg-surface-2 px-4 text-left text-small transition hover:border-border md:flex"
                            aria-label="Search the docs"
                        >
                            <i
                                className="fa-solid fa-magnifying-glass text-[14px] text-text-muted"
                                aria-hidden
                            />
                            <span className="flex-1 text-text-muted">Search the docs</span>
                            <span className="ml-auto inline-flex gap-1">
                                <kbd className="rounded-[5px] border border-border-subtle bg-surface px-[7px] py-[2px] font-mono text-[11px] text-text-muted">
                                    ⌘
                                </kbd>
                                <kbd className="rounded-[5px] border border-border-subtle bg-surface px-[7px] py-[2px] font-mono text-[11px] text-text-muted">
                                    K
                                </kbd>
                            </span>
                        </button>

                        <div className="ml-auto flex items-center gap-1.5">
                            <button
                                type="button"
                                onClick={openSearch}
                                className="inline-flex h-[38px] w-[38px] items-center justify-center rounded-[9px] border border-border-subtle bg-surface-2 text-text-muted transition hover:text-text md:hidden"
                                aria-label="Open search"
                            >
                                <i
                                    className="fa-solid fa-magnifying-glass text-[14px]"
                                    aria-hidden
                                />
                            </button>
                            <ThemeToggle />
                            <span
                                className="mx-1.5 hidden h-[22px] w-px bg-border-subtle md:block"
                                aria-hidden
                            />
                            <SocialIconLink
                                href="https://github.com/ArtisanPack-UI"
                                icon="fa-brands fa-github"
                                label="GitHub"
                            />
                            <BlueskyIconLink href="https://bsky.app/profile/artisanpackui.dev" />
                            <SocialIconLink
                                href="https://mastodon.social/@artisanpackui"
                                icon="fa-brands fa-mastodon"
                                label="Mastodon"
                            />
                        </div>
                    </div>
                    <div
                        className="h-[2px] w-full opacity-85"
                        style={{ backgroundImage: 'var(--grad-neon)' }}
                        aria-hidden
                    />
                </header>

                <div className="grid w-full grid-cols-1 xl:grid-cols-[290px_1fr_264px]">
                    <aside
                        className="sticky hidden self-start overflow-y-auto border-r border-border-subtle px-4 py-6 xl:block"
                        style={{
                            top: 'var(--docs-header-h, 76px)',
                            maxHeight: 'calc(100vh - var(--docs-header-h, 76px))',
                            background: 'linear-gradient(180deg, #080C16 0%, #05070E 100%)',
                        }}
                        aria-label="Documentation navigation"
                    >
                        {sidebar}
                    </aside>

                    <main className="min-w-0 px-6 py-10 md:px-16 md:py-14">
                        {children}

                        {toc && !isDesktop ? (
                            <div className="mt-12 border-t border-border-subtle pt-8">{toc}</div>
                        ) : null}
                    </main>

                    <aside
                        className="sticky hidden self-start overflow-y-auto px-6 py-10 xl:block"
                        style={{
                            top: 'var(--docs-header-h, 76px)',
                            maxHeight: 'calc(100vh - var(--docs-header-h, 76px))',
                        }}
                        aria-label="Table of contents"
                    >
                        {isDesktop ? toc : null}
                    </aside>
                </div>

                {sidebar ? (
                    <div
                        className={`fixed inset-0 z-50 xl:hidden ${mobileNavOpen ? '' : 'pointer-events-none'}`}
                        aria-hidden={!mobileNavOpen}
                    >
                        <div
                            className={`absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-200 ${mobileNavOpen ? 'opacity-100' : 'opacity-0'}`}
                            onClick={() => setMobileNavOpen(false)}
                        />
                        <aside
                            id="docs-mobile-nav"
                            className={`absolute inset-y-0 left-0 flex w-[290px] max-w-[85vw] flex-col border-r border-border-subtle transition-transform duration-200 ${mobileNavOpen ? 'translate-x-0' : '-translate-x-full'}`}
                            style={{
                                background: 'linear-gradient(180deg, #080C16 0%, #05070E 100%)',
                            }}
                            aria-label="Documentation navigation"
                            // Removes the drawer's contents from tab
                            // order + AT semantics when closed, which
                            // aria-hidden + pointer-events-none don't
                            // guarantee on their own.
                            inert={!mobileNavOpen}
                        >
                            <div className="flex items-center justify-between border-b border-border-subtle px-4 py-4">
                                <span className="font-display text-sm font-semibold uppercase tracking-[0.14em] text-text-muted">
                                    Menu
                                </span>
                                <button
                                    type="button"
                                    onClick={() => setMobileNavOpen(false)}
                                    className="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[8px] border border-border-subtle bg-surface-2 text-text-muted transition hover:text-text"
                                    aria-label="Close navigation"
                                >
                                    <i className="fa-solid fa-xmark text-[15px]" aria-hidden />
                                </button>
                            </div>
                            <div className="flex-1 overflow-y-auto px-4 py-6">{sidebar}</div>
                        </aside>
                    </div>
                ) : null}

                <footer
                    className="flex flex-col items-center gap-2 border-t-2 py-6 text-center"
                    style={{
                        borderTopColor: 'var(--color-primary)',
                        background: 'var(--ap-ink)',
                    }}
                >
                    <span className="font-display text-sm font-bold text-text">
                        © ArtisanPack UI {new Date().getFullYear()}
                    </span>
                    <Link
                        href="/policy"
                        className="cursor-pointer text-[13px] text-text-muted transition hover:text-secondary"
                    >
                        Privacy
                    </Link>
                </footer>

                {searchOpen ? <SearchOverlay onClose={closeSearch} /> : null}

                <PrivacyBanners />
            </div>
        </>
    );
}

function SocialIconLink({ href, icon, label }: { href: string; icon: string; label: string }) {
    return (
        <a
            href={href}
            aria-label={label}
            className="inline-flex h-[38px] w-[38px] items-center justify-center rounded-[9px] text-text-muted transition hover:text-secondary"
        >
            <i className={`${icon} text-[16px]`} aria-hidden />
        </a>
    );
}

// Font Awesome 6.5.1 does not ship the Bluesky glyph, so the butterfly
// mark is inlined as SVG.
function BlueskyIconLink({ href }: { href: string }) {
    return (
        <a
            href={href}
            aria-label="Bluesky"
            className="inline-flex h-[38px] w-[38px] items-center justify-center rounded-[9px] text-text-muted transition hover:text-secondary"
        >
            <svg
                viewBox="0 0 600 530"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                width="17"
                height="15"
                aria-hidden
            >
                <path d="M135.72 44.03C202.216 93.951 273.74 195.17 300 249.49c26.262-54.316 97.782-155.539 164.28-205.46C512.246 8.008 590-19.796 590 68.906c0 17.712-10.155 148.79-16.111 170.07-20.703 73.984-96.144 92.854-163.25 81.433 117.3 19.964 147.14 86.092 82.697 152.22-122.39 125.59-175.91-31.511-189.63-71.766-2.514-7.38-3.69-10.832-3.708-7.896-.017-2.936-1.193.516-3.707 7.896-13.714 40.255-67.233 197.36-189.63 71.766-64.444-66.128-34.605-132.26 82.697-152.22-67.108 11.421-142.55-7.45-163.25-81.433C20.15 217.7 9.997 86.618 9.997 68.906c0-88.702 77.754-60.898 125.72-24.876z" />
            </svg>
        </a>
    );
}

export default DocsLayout;
