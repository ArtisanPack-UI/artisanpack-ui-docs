import { Head } from '@inertiajs/react';

import { DocsLayout } from '../../../../../resources/js/layouts/DocsLayout';
import { MainSidebar } from '../../../../Core/resources/js/components/MainSidebar';
import { NewsletterCard } from '../../../../Core/resources/js/components/NewsletterCard';
import { TableOfContents } from '../../../../Core/resources/js/components/TableOfContents';
import type { SidebarNavigation, TocHeading } from '../../../../Core/resources/js/types/navigation';
import type { PageProps } from '../../../../../resources/js/types/pages';

export type PageShowProps = PageProps<{
    page: {
        title: string;
        metaDescription: string;
        content: string;
        tableOfContents: TocHeading[];
        slug: string;
        parentSlug: string | null;
        parentTitle: string | null;
    };
    navigation: SidebarNavigation;
}>;

export default function Show({ page, navigation }: PageShowProps) {
    return (
        <DocsLayout
            sidebar={<MainSidebar navigation={navigation} />}
            toc={
                <div className="flex flex-col gap-6">
                    <TableOfContents headings={page.tableOfContents} />
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
            <Head title={page.title}>
                {page.metaDescription ? (
                    <meta name="description" content={page.metaDescription} />
                ) : null}
            </Head>

            <div className="mb-5 flex items-center gap-2 font-mono text-[13px] text-text-subtle">
                <span>Docs</span>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                {page.parentTitle ? (
                    <>
                        <span>{page.parentTitle}</span>
                        <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                    </>
                ) : null}
                <span className="text-text-muted">{page.title}</span>
            </div>

            <h1 className="mb-5 font-display text-[46px] font-extrabold leading-[1.1] tracking-[-0.02em] text-text">
                {page.title}
            </h1>

            {page.metaDescription ? (
                <p className="mb-10 max-w-[660px] text-[19px] leading-[1.6] text-text-muted">
                    {page.metaDescription}
                </p>
            ) : null}

            <div className="mb-9 h-px w-full bg-border-subtle" aria-hidden />

            <article
                className="max-w-[680px] docs-prose text-[17px] leading-[1.75] text-text-muted"
                dangerouslySetInnerHTML={{ __html: page.content }}
            />
        </DocsLayout>
    );
}
