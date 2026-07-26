import { Head } from '@inertiajs/react';
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

export type ChangelogShowProps = PageProps<{
    package: {
        name: string;
        slug: string;
        version: string | null;
    };
    changelog: {
        title: string;
        content: string;
        tableOfContents: TocHeading[];
    };
    navigation: SidebarNavigation;
}>;

export default function Show({ package: pkg, changelog, navigation }: ChangelogShowProps) {
    const articleRef = useRef<HTMLElement>(null);
    useCopyableCodeBlocks(articleRef, `${pkg.slug}:changelog`);

    return (
        <DocsLayout
            sidebar={<MainSidebar navigation={navigation} />}
            toc={
                <div className="flex flex-col gap-6">
                    <TableOfContents headings={changelog.tableOfContents} />
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
            <Head title={`${pkg.name} changelog`} />

            <div className="mb-5 flex items-center gap-2 font-mono text-[13px] text-text-subtle">
                <span>Docs</span>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                <span>{pkg.name}</span>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                <span className="text-text-muted">Changelog</span>
            </div>

            <div className="mb-5 flex flex-wrap items-baseline gap-3">
                <h1 className="font-display text-[46px] font-extrabold leading-[1.1] tracking-[-0.02em] text-text">
                    {changelog.title}
                </h1>
                {pkg.version ? (
                    <span className="font-mono text-[12px] uppercase tracking-[0.14em] text-text-subtle">
                        Latest: v{pkg.version}
                    </span>
                ) : null}
            </div>

            <div className="mb-9 h-px w-full bg-border-subtle" aria-hidden />

            <article
                ref={articleRef}
                className="max-w-[680px] docs-prose text-[17px] leading-[1.75] text-text-muted"
                dangerouslySetInnerHTML={{ __html: changelog.content }}
            />
        </DocsLayout>
    );
}
