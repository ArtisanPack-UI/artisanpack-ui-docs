import { Head, Link } from '@inertiajs/react';
import { useRef } from 'react';

import { DocsLayout } from '../../../../../../resources/js/layouts/DocsLayout';
import { MainSidebar } from '../../../../../Core/resources/js/components/MainSidebar';
import { NewsletterCard } from '../../../../../Core/resources/js/components/NewsletterCard';
import { TableOfContents } from '../../../../../Core/resources/js/components/TableOfContents';
import type {
    SidebarNavigation,
    TocHeading,
} from '../../../../../Core/resources/js/types/navigation';
import type { PageProps } from '../../../../../../resources/js/types/pages';
import { useCopyableCodeBlocks } from '../../hooks/useCopyableCodeBlocks';

interface NeighborLink {
    title: string;
    url: string;
}

export type DocumentationShowProps = PageProps<{
    package: {
        name: string;
        slug: string;
        version: string | null;
    };
    doc: {
        title: string;
        slug: string;
        metaDescription: string;
        content: string;
        tableOfContents: TocHeading[];
    };
    previous: NeighborLink | null;
    next: NeighborLink | null;
    navigation: SidebarNavigation;
}>;

export default function Show({
    package: pkg,
    doc,
    previous,
    next,
    navigation,
}: DocumentationShowProps) {
    const articleRef = useRef<HTMLElement>(null);
    useCopyableCodeBlocks(articleRef, `${pkg.slug}/${doc.slug}`);

    return (
        <DocsLayout
            sidebar={<MainSidebar navigation={navigation} />}
            toc={
                <div className="flex flex-col gap-6">
                    <TableOfContents headings={doc.tableOfContents} />
                    <NewsletterCard />
                    <a
                        href="https://github.com/ArtisanPack-UI/artisanpack-ui-docs"
                        className="flex items-center gap-2 text-[13px] text-text-subtle transition hover:text-text"
                    >
                        <i className="fa-brands fa-github" aria-hidden />
                        Edit this page on GitHub
                    </a>
                </div>
            }
        >
            <Head title={doc.title}>
                {doc.metaDescription ? (
                    <meta name="description" content={doc.metaDescription} />
                ) : null}
            </Head>

            <div className="mb-5 flex items-center gap-2 font-mono text-[13px] text-text-subtle">
                <span>Docs</span>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                <span>{pkg.name}</span>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                <span className="text-text-muted">{doc.title}</span>
            </div>

            <div className="mb-5 flex flex-wrap items-baseline gap-3">
                <h1 className="font-display text-[46px] font-extrabold leading-[1.1] tracking-[-0.02em] text-text">
                    {doc.title}
                </h1>
                {pkg.version ? (
                    <span className="font-mono text-[12px] uppercase tracking-[0.14em] text-text-subtle">
                        {pkg.name} v{pkg.version}
                    </span>
                ) : null}
            </div>

            {doc.metaDescription ? (
                <p className="mb-10 max-w-[660px] text-[19px] leading-[1.6] text-text-muted">
                    {doc.metaDescription}
                </p>
            ) : null}

            <div className="mb-9 h-px w-full bg-border-subtle" aria-hidden />

            <article
                ref={articleRef}
                className="max-w-[680px] docs-prose text-[17px] leading-[1.75] text-text-muted"
                dangerouslySetInnerHTML={{ __html: doc.content }}
            />

            {previous || next ? (
                <nav
                    aria-label="Documentation navigation"
                    className="mt-16 grid gap-4 border-t border-border-subtle pt-8 sm:grid-cols-2"
                >
                    {previous ? (
                        <NeighborCard direction="previous" link={previous} />
                    ) : (
                        <span aria-hidden />
                    )}
                    {next ? <NeighborCard direction="next" link={next} /> : <span aria-hidden />}
                </nav>
            ) : null}
        </DocsLayout>
    );
}

function NeighborCard({ direction, link }: { direction: 'previous' | 'next'; link: NeighborLink }) {
    const isNext = direction === 'next';
    return (
        <Link
            href={link.url}
            className={`group flex flex-col gap-1 rounded-[10px] border border-border-subtle bg-surface-2 px-5 py-4 transition hover:border-border ${
                isNext ? 'sm:text-right' : ''
            }`}
        >
            <span
                className={`inline-flex items-center gap-1.5 font-mono text-[11px] uppercase tracking-[0.16em] text-text-subtle ${
                    isNext ? 'justify-end' : ''
                }`}
            >
                {isNext ? null : <i className="fa-solid fa-arrow-left text-[10px]" aria-hidden />}
                {isNext ? 'Next' : 'Previous'}
                {isNext ? <i className="fa-solid fa-arrow-right text-[10px]" aria-hidden /> : null}
            </span>
            <span className="text-[15px] font-semibold text-text transition group-hover:text-secondary">
                {link.title}
            </span>
        </Link>
    );
}
