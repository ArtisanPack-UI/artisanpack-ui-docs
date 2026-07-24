import { Head } from '@inertiajs/react';

import { DocsLayout } from '../../../../../resources/js/layouts/DocsLayout';
import { MainSidebar } from '../components/MainSidebar';
import { NewsletterCard } from '../components/NewsletterCard';
import { TableOfContents } from '../components/TableOfContents';
import type { PageProps } from '../../../../../resources/js/types/pages';
import type { SidebarNavigation, TocHeading } from '../types/navigation';

export type HomePageProps = PageProps<{
    page: {
        title: string;
        metaDescription: string;
        content: string;
        tableOfContents: TocHeading[];
    };
    navigation: SidebarNavigation;
}>;

export default function Home({ page, navigation }: HomePageProps) {
    const hasContent = page.title !== '' || page.content !== '';
    const displayTitle = page.title || 'Welcome to ArtisanPack UI';

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
            <Head title={page.title || undefined}>
                {page.metaDescription ? (
                    <meta name="description" content={page.metaDescription} />
                ) : null}
            </Head>

            <div className="mb-5 flex items-center gap-2 font-mono text-[13px] text-text-subtle">
                <span>Docs</span>
                <i
                    className="fa-solid fa-chevron-right text-[9px]"
                    aria-hidden
                />
                <span>Guides</span>
                <i
                    className="fa-solid fa-chevron-right text-[9px]"
                    aria-hidden
                />
                <span className="text-text-muted">Welcome</span>
            </div>

            <h1 className="mb-5 font-display text-[46px] font-extrabold leading-[1.1] tracking-[-0.02em] text-text">
                {displayTitle}
            </h1>

            {hasContent && page.metaDescription ? (
                <p className="mb-10 max-w-[660px] text-[19px] leading-[1.6] text-text-muted">
                    {page.metaDescription}
                </p>
            ) : !hasContent ? (
                <p className="mb-10 max-w-[660px] text-[19px] leading-[1.6] text-text-muted">
                    No home page has been configured yet. Set one in the admin
                    dashboard to display it here.
                </p>
            ) : null}

            <div className="mb-9 h-px w-full bg-border-subtle" aria-hidden />

            {hasContent ? (
                <article
                    className="max-w-[680px] docs-prose text-[17px] leading-[1.75] text-text-muted"
                    dangerouslySetInnerHTML={{ __html: page.content }}
                />
            ) : null}
        </DocsLayout>
    );
}
