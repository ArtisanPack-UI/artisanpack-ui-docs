import { Head, Link } from '@inertiajs/react';

import { MainSidebar } from '../../../../Modules/Core/resources/js/components/MainSidebar';
import type { SidebarNavigation } from '../../../../Modules/Core/resources/js/types/navigation';
import { DocsLayout } from '@/layouts/DocsLayout';

interface PolicySection {
    id: string;
    title: string;
    level?: number;
}

interface PolicyHistoryEntry {
    version: string;
    locale: string;
    regulation: string | null;
    published_at: string | null;
    url: string;
}

interface PolicyProps {
    policy: {
        version: string;
        regulation: string | null;
        locale: string;
        published_at: string | null;
        is_active: boolean;
        html: string;
        sections: PolicySection[];
    };
    history: PolicyHistoryEntry[];
    locale: string;
    policy_url: string;
    navigation: SidebarNavigation;
}

function formatDate(iso: string | null): string {
    if (iso === null) {
        return '—';
    }
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) {
        return iso;
    }
    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

export default function Policy({ policy, history, policy_url, navigation }: PolicyProps) {
    const meta =
        [
            `Version ${policy.version}`,
            policy.published_at ? `Published ${formatDate(policy.published_at)}` : null,
            policy.regulation ? policy.regulation.toUpperCase() : null,
            policy.is_active ? null : 'Archived version',
        ]
            .filter(Boolean)
            .join(' · ') || null;

    const toc =
        policy.sections.length > 0 || history.length > 0 ? (
            <div className="flex flex-col gap-6">
                {policy.sections.length > 0 ? (
                    <nav
                        aria-label="On this page"
                        className="rounded-[10px] border border-border-subtle bg-surface-2/70 p-4 text-[13px]"
                    >
                        <p className="mb-2 font-semibold text-text">On this page</p>
                        <ul className="flex flex-col gap-1 text-text-muted">
                            {policy.sections.map((section) => (
                                <li key={section.id}>
                                    <a href={`#${section.id}`} className="hover:text-secondary">
                                        {section.title}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </nav>
                ) : null}

                {history.length > 0 ? (
                    <nav
                        aria-label="Policy history"
                        className="rounded-[10px] border border-border-subtle bg-surface-2/70 p-4 text-[13px]"
                    >
                        <p className="mb-2 font-semibold text-text">Version history</p>
                        <ul className="flex flex-col gap-1 text-text-muted">
                            {history.map((entry) => (
                                <li key={`${entry.version}-${entry.locale}`}>
                                    <Link
                                        href={
                                            entry.version === policy.version
                                                ? policy_url
                                                : entry.url
                                        }
                                        className={
                                            entry.version === policy.version
                                                ? 'text-text'
                                                : 'hover:text-secondary'
                                        }
                                    >
                                        v{entry.version} · {formatDate(entry.published_at)}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>
                ) : null}
            </div>
        ) : undefined;

    return (
        <DocsLayout sidebar={<MainSidebar navigation={navigation} />} toc={toc}>
            <Head title="Privacy Policy" />

            <div className="mb-5 flex items-center gap-2 font-mono text-[13px] text-text-subtle">
                <Link href="/" className="hover:text-secondary">
                    Home
                </Link>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                <span className="text-text-muted">Privacy Policy</span>
            </div>

            <h1 className="mb-5 font-display text-[46px] font-extrabold leading-[1.1] tracking-[-0.02em] text-text">
                Privacy Policy
            </h1>

            {meta !== null ? (
                <p className="mb-10 max-w-[660px] text-[17px] leading-[1.6] text-text-muted">
                    {meta}
                </p>
            ) : null}

            <div className="mb-9 h-px w-full bg-border-subtle" aria-hidden />

            <article
                className="max-w-[680px] docs-prose text-[17px] leading-[1.75] text-text-muted"
                dangerouslySetInnerHTML={{ __html: policy.html }}
            />
        </DocsLayout>
    );
}
