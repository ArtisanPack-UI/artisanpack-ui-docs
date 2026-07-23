import { Link } from '@inertiajs/react';
import { ThemeToggle } from '@artisanpack-ui/react';
import type { ReactNode } from 'react';

const SEARCH_ICON_PATH = 'M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z';
const GITHUB_ICON_PATH =
    'M12 .5a11.5 11.5 0 00-3.63 22.42c.58.1.79-.25.79-.56v-2.01c-3.2.7-3.88-1.36-3.88-1.36-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.71.08-.71 1.17.08 1.79 1.2 1.79 1.2 1.03 1.77 2.71 1.26 3.37.96.1-.75.4-1.26.73-1.55-2.56-.29-5.26-1.28-5.26-5.7 0-1.26.45-2.29 1.19-3.1-.12-.29-.51-1.47.11-3.06 0 0 .97-.31 3.18 1.18a11.03 11.03 0 015.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.24 2.77.12 3.06.74.81 1.19 1.84 1.19 3.1 0 4.43-2.7 5.4-5.27 5.69.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.55A11.5 11.5 0 0012 .5z';

export interface DocsLayoutProps {
    children: ReactNode;
    sidebar?: ReactNode;
    toc?: ReactNode;
    onSearchOpen?: () => void;
}

export function DocsLayout({ children, sidebar, toc, onSearchOpen }: DocsLayoutProps) {
    return (
        <div className="min-h-screen bg-base text-text">
            <header
                className="sticky top-0 z-40 backdrop-blur-[14px]"
                style={{ backgroundColor: 'rgba(8, 12, 22, 0.9)' }}
            >
                <div className="mx-auto flex w-full max-w-[1440px] items-center justify-between gap-6 px-6 py-4">
                    <Link href="/" className="font-display text-lg font-semibold tracking-tight">
                        ArtisanPack UI
                    </Link>

                    <button
                        type="button"
                        onClick={onSearchOpen}
                        className="hidden max-w-md flex-1 items-center gap-2 rounded-box border border-border-subtle bg-surface px-4 py-2 text-left text-small text-text-muted transition hover:border-border md:flex"
                        aria-label="Open search (⌘K)"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            width="16"
                            height="16"
                            aria-hidden
                        >
                            <path d={SEARCH_ICON_PATH} />
                        </svg>
                        <span className="flex-1">Search the docs…</span>
                        <kbd className="rounded border border-border-subtle px-1.5 py-0.5 font-mono text-xsmall">⌘K</kbd>
                    </button>

                    <div className="flex items-center gap-3">
                        <ThemeToggle />
                        <a
                            href="https://github.com/ArtisanPack-UI"
                            aria-label="GitHub"
                            className="text-text-muted transition hover:text-secondary"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                width="20"
                                height="20"
                                aria-hidden
                            >
                                <path d={GITHUB_ICON_PATH} />
                            </svg>
                        </a>
                    </div>
                </div>
                <div className="h-[2px] w-full" style={{ backgroundImage: 'var(--grad-neon)' }} aria-hidden />
            </header>

            <div
                className="mx-auto grid w-full max-w-[1440px] gap-8 px-6 py-8"
                style={{ gridTemplateColumns: '290px 1fr 264px' }}
            >
                <aside className="sticky top-[89px] h-[calc(100vh-89px)] overflow-y-auto pr-2" aria-label="Documentation navigation">
                    {sidebar}
                </aside>

                <main className="min-w-0">{children}</main>

                <aside className="sticky top-[89px] h-[calc(100vh-89px)] overflow-y-auto pl-2" aria-label="Table of contents">
                    {toc}
                </aside>
            </div>
        </div>
    );
}

export default DocsLayout;
