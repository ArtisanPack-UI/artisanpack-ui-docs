import { Link } from '@inertiajs/react';
import { useMemo, useState, type ReactNode } from 'react';

import { SidebarIcon } from './SidebarIcon';
import type {
    SidebarDocNode,
    SidebarNavigation,
    SidebarPackage,
    SidebarPageNode,
} from '../types/navigation';

export interface MainSidebarProps {
    navigation: SidebarNavigation;
}

const DEFAULT_PAGE_ICON = 'fa-solid fa-file-lines';
const DEFAULT_PACKAGE_ICON = 'fa-solid fa-cube';

export function MainSidebar({ navigation }: MainSidebarProps) {
    const [filter, setFilter] = useState('');

    const filteredPages = useMemo(
        () => filterPages(navigation.pages, filter),
        [navigation.pages, filter],
    );
    const filteredPackages = useMemo(
        () => filterPackages(navigation.packages, filter),
        [navigation.packages, filter],
    );

    const hasPages = filteredPages.length > 0;
    const hasPackages = filteredPackages.length > 0;

    return (
        <div className="flex flex-col gap-1.5">
            <label className="mb-3.5 flex h-10 items-center gap-2.5 rounded-[9px] border border-border-subtle bg-surface-2 px-3">
                <i
                    className="fa-solid fa-magnifying-glass text-[13px] text-text-subtle"
                    aria-hidden
                />
                <input
                    type="search"
                    value={filter}
                    onChange={(event) => setFilter(event.target.value)}
                    placeholder="Filter pages…"
                    aria-label="Filter navigation"
                    className="min-w-0 flex-1 bg-transparent text-small text-text placeholder:text-text-subtle focus:outline-none"
                />
            </label>

            {hasPages ? (
                <>
                    <SectionLabel label="Guides" />
                    <nav aria-label="Pages" className="flex flex-col gap-0.5">
                        {filteredPages.map((page) => (
                            <PageItem key={page.id} page={page} />
                        ))}
                    </nav>
                </>
            ) : null}

            {hasPages && hasPackages ? (
                <hr className="my-4 border-border-subtle" aria-hidden />
            ) : null}

            {hasPackages ? (
                <>
                    <SectionLabel label="Packages" count={navigation.packages.length} />
                    <nav aria-label="Packages" className="flex flex-col gap-0.5">
                        {filteredPackages.map((pkg) => (
                            <PackageItem key={pkg.id} pkg={pkg} />
                        ))}
                    </nav>
                </>
            ) : null}

            {!hasPages && !hasPackages ? (
                <p className="px-3 py-2 text-small text-text-subtle">No matches.</p>
            ) : null}
        </div>
    );
}

function SectionLabel({ label, count }: { label: string; count?: number }) {
    return (
        <div className="flex items-center justify-between px-3 pb-2 pt-1.5">
            <span className="font-mono text-[11px] uppercase tracking-[0.16em] text-text-subtle">
                {label}
            </span>
            {typeof count === 'number' ? (
                <span className="font-mono text-[11px] text-border-strong">{count}</span>
            ) : null}
        </div>
    );
}

/**
 * Uncontrolled `<details>` with a *default* open state.
 *
 * React treats the `open` attribute on `<details>` as a controlled
 * prop: passing it locks the element to that value on every render,
 * which would snap a user-opened package shut the moment anything else
 * re-renders the sidebar (e.g. typing in the filter). Passing `open`
 * only in the initial `useState` snapshot and never touching it again
 * gives users a native, uncontrolled toggle while still honoring the
 * server's "this branch is active, open it by default" hint.
 */
function ExpandingDetails({
    defaultOpen,
    className,
    children,
}: {
    defaultOpen: boolean;
    className?: string;
    children: ReactNode;
}) {
    const [initialOpen] = useState(defaultOpen);
    return (
        <details
            className={`group/details ${className ?? ''}`}
            {...(initialOpen ? { open: true } : {})}
        >
            {children}
        </details>
    );
}

function pageHref(page: SidebarPageNode, parentSlug?: string): string {
    if (parentSlug) {
        return `/${parentSlug}/${page.slug}`;
    }
    return `/${page.slug}`;
}

function PageItem({ page, parentSlug }: { page: SidebarPageNode; parentSlug?: string }): ReactNode {
    const hasChildren = !!page.children && page.children.length > 0;
    const href = pageHref(page, parentSlug);

    if (hasChildren) {
        return (
            <ExpandingDetails defaultOpen={page.active} className="rounded-[9px]">
                <summary
                    className={`flex cursor-pointer list-none items-center gap-3 rounded-[9px] px-3.5 py-2.5 text-[15px] transition hover:bg-white/[0.02] ${
                        page.active ? 'text-text' : 'text-text-muted hover:text-text'
                    }`}
                >
                    <SidebarIcon
                        icon={page.icon}
                        fallbackClass={DEFAULT_PAGE_ICON}
                        tone={page.active ? 'active' : 'muted'}
                    />
                    <span className="flex-1 truncate">{page.title}</span>
                    <ChevronToggle />
                </summary>
                <div className="ml-[14px] mt-1 flex flex-col border-l border-white/10 pl-3">
                    <NestedLink href={href} active={page.isCurrentPage}>
                        {page.title}
                    </NestedLink>
                    {page.children!.map((child) => (
                        <PageItem key={child.id} page={child} parentSlug={page.slug} />
                    ))}
                </div>
            </ExpandingDetails>
        );
    }

    return (
        <TopLevelLink
            href={href}
            icon={page.icon}
            fallbackIcon={DEFAULT_PAGE_ICON}
            active={page.isCurrentPage}
        >
            {page.title}
        </TopLevelLink>
    );
}

function PackageItem({ pkg }: { pkg: SidebarPackage }): ReactNode {
    const hasDocs = pkg.documentation.length > 0;
    const hasHomepage = pkg.homepage !== null;
    const hasChangelog = pkg.changelog !== null;

    if (!hasDocs && !hasHomepage && !hasChangelog) {
        return null;
    }

    return (
        <ExpandingDetails defaultOpen={pkg.active} className="rounded-[9px]">
            <summary
                className={`flex cursor-pointer list-none items-center gap-3 rounded-[9px] px-3.5 py-2.5 text-[15px] transition hover:bg-white/[0.02] ${
                    pkg.active ? 'text-text' : 'text-text-muted hover:text-text'
                }`}
            >
                <SidebarIcon
                    icon={pkg.icon}
                    fallbackClass={DEFAULT_PACKAGE_ICON}
                    tone={pkg.active ? 'active' : 'muted'}
                />
                <span className="flex-1 truncate">{pkg.name}</span>
                <ChevronToggle />
            </summary>
            <div
                className="ml-[14px] mt-1 flex flex-col border-l pl-3"
                style={{ borderColor: 'rgba(0,229,255,0.25)' }}
            >
                {pkg.homepage ? (
                    <NestedLink
                        href={`/documentation/${pkg.slug}/${pkg.homepage.slug}`}
                        active={pkg.homepage.active}
                    >
                        {pkg.homepage.title}
                    </NestedLink>
                ) : null}
                {pkg.documentation.map((doc) => (
                    <DocItem key={doc.id} doc={doc} packageSlug={pkg.slug} />
                ))}
                {pkg.changelog ? (
                    <NestedLink href={`/changelogs/${pkg.slug}`} active={pkg.changelog.active}>
                        {pkg.changelog.title}
                    </NestedLink>
                ) : null}
            </div>
        </ExpandingDetails>
    );
}

function DocItem({ doc, packageSlug }: { doc: SidebarDocNode; packageSlug: string }): ReactNode {
    const hasChildren = !!doc.children && doc.children.length > 0;
    const href = `/documentation/${packageSlug}/${doc.slug}`;

    if (hasChildren) {
        return (
            <ExpandingDetails defaultOpen={doc.active} className="rounded-[7px]">
                <summary
                    className={`flex cursor-pointer list-none items-center gap-2 rounded-[7px] px-3 py-1.5 text-[14px] transition hover:bg-white/[0.02] ${
                        doc.active ? 'text-text' : 'text-text-muted hover:text-text'
                    }`}
                >
                    <span className="flex-1 truncate">{doc.title}</span>
                    <ChevronToggle small />
                </summary>
                <div className="ml-3 mt-1 flex flex-col border-l border-white/10 pl-3">
                    <NestedLink href={href} active={doc.isCurrentPage}>
                        {doc.title}
                    </NestedLink>
                    {doc.children!.map((child) => (
                        <DocItem key={child.id} doc={child} packageSlug={packageSlug} />
                    ))}
                </div>
            </ExpandingDetails>
        );
    }

    return (
        <NestedLink href={href} active={doc.isCurrentPage}>
            {doc.title}
        </NestedLink>
    );
}

function TopLevelLink({
    href,
    icon,
    fallbackIcon,
    active,
    children,
}: {
    href: string;
    icon: SidebarPageNode['icon'];
    fallbackIcon: string;
    active: boolean;
    children: ReactNode;
}) {
    if (active) {
        return (
            <Link
                href={href}
                aria-current="page"
                className="relative flex items-center gap-3 rounded-[9px] border px-3.5 py-2.5 text-[15px] font-semibold text-text"
                style={{
                    background:
                        'linear-gradient(90deg, rgba(41,98,255,0.16), rgba(224,64,251,0.06))',
                    borderColor: 'rgba(0,229,255,0.22)',
                }}
            >
                <span
                    className="absolute bottom-2 left-0 top-2 w-[3px] rounded"
                    style={{ background: 'var(--grad-neon)' }}
                    aria-hidden
                />
                <SidebarIcon icon={icon} fallbackClass={fallbackIcon} tone="active" />
                <span className="flex-1 truncate">{children}</span>
            </Link>
        );
    }

    return (
        <Link
            href={href}
            className="flex items-center gap-3 rounded-[9px] px-3.5 py-2.5 text-[15px] text-text-muted transition hover:bg-white/[0.02] hover:text-text"
        >
            <SidebarIcon icon={icon} fallbackClass={fallbackIcon} tone="muted" />
            <span className="flex-1 truncate">{children}</span>
        </Link>
    );
}

function NestedLink({
    href,
    active,
    children,
}: {
    href: string;
    active: boolean;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={`rounded-[7px] px-3 py-1.5 text-[14px] transition ${
                active
                    ? 'font-semibold text-text'
                    : 'text-text-muted hover:bg-white/[0.02] hover:text-text'
            }`}
            style={active ? { background: 'rgba(0,229,255,0.10)' } : undefined}
        >
            {children}
        </Link>
    );
}

function ChevronToggle({ small = false }: { small?: boolean }) {
    const size = small ? 'text-[10px]' : 'text-[11px]';
    return (
        <i
            className={`fa-solid fa-chevron-right text-border-strong transition-transform duration-150 group-open/details:rotate-90 ${size}`}
            aria-hidden
        />
    );
}

function filterPages(pages: SidebarPageNode[], query: string): SidebarPageNode[] {
    if (query.trim() === '') {
        return pages;
    }
    const q = query.toLowerCase();
    const result: SidebarPageNode[] = [];
    for (const page of pages) {
        const children = page.children ? filterPages(page.children, query) : undefined;
        const selfMatches = page.title.toLowerCase().includes(q);
        if (selfMatches || (children && children.length > 0)) {
            const next: SidebarPageNode = { ...page };
            if (children !== undefined) {
                next.children = children;
            }
            result.push(next);
        }
    }
    return result;
}

function filterPackages(packages: SidebarPackage[], query: string): SidebarPackage[] {
    if (query.trim() === '') {
        return packages;
    }
    const q = query.toLowerCase();
    return packages.filter((pkg) => {
        if (pkg.name.toLowerCase().includes(q)) {
            return true;
        }
        const anyDocMatches = (docs: SidebarDocNode[]): boolean =>
            docs.some(
                (doc) =>
                    doc.title.toLowerCase().includes(q) ||
                    (doc.children ? anyDocMatches(doc.children) : false),
            );
        return anyDocMatches(pkg.documentation);
    });
}

export default MainSidebar;
