import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { DocsLayout } from '../../../../../../resources/js/layouts/DocsLayout';
import { MainSidebar } from '../../../../../Core/resources/js/components/MainSidebar';
import { TableOfContents } from '../../../../../Core/resources/js/components/TableOfContents';
import type {
    SidebarNavigation,
    TocHeading,
} from '../../../../../Core/resources/js/types/navigation';
import type { PageProps } from '../../../../../../resources/js/types/pages';
import { CodeBlock } from '../../components/DesignSystem/CodeBlock';
import { ExamplePreview } from '../../components/DesignSystem/ExamplePreview';
import { ManifestTables } from '../../components/DesignSystem/ManifestTables';
import { StackTabs } from '../../components/DesignSystem/StackTabs';
import {
    STACK_LABELS,
    TABLE_SECTIONS,
    stackPanelId,
    stackTabId,
    type ComponentExample,
    type ComponentStack,
    type ManifestTables as Tables,
    type StackKey,
} from '../../components/DesignSystem/types';

export type DesignSystemComponentProps = PageProps<{
    component: {
        name: string;
        slug: string;
        description: string;
        status: string;
        stacks: ComponentStack[];
        tables: Partial<Record<StackKey, Tables>>;
    };
    examples: ComponentExample[];
    navigation: SidebarNavigation;
}>;

const CODE_LANGUAGES: Record<StackKey, string> = {
    livewire: 'blade',
    react: 'tsx',
    vue: 'vue',
};

const TAB_ID_PREFIX = 'stack';

/**
 * Design-system component page (design-system#43): Blade / React / Vue
 * tabs, each with that stack's examples (preview plus source) and the
 * props, parts, slots and events tables from the component's manifest.
 */
export default function Component({ component, examples, navigation }: DesignSystemComponentProps) {
    const [activeStack, setActiveStack] = useState<StackKey | undefined>(component.stacks[0]?.key);

    const tables = activeStack ? component.tables[activeStack] : undefined;
    const stackExamples = useMemo(
        () => (activeStack ? examples.filter((example) => example.sources[activeStack]) : []),
        [examples, activeStack],
    );

    const headings = useMemo<TocHeading[]>(() => {
        const list: TocHeading[] = [];
        if (stackExamples.length > 0) {
            list.push({
                id: 'examples',
                text: 'Examples',
                level: 2,
                children: stackExamples.map((example) => ({
                    id: exampleId(example),
                    text: example.title,
                    level: 3,
                })),
            });
        }
        if (tables) {
            const sections: Array<[keyof typeof TABLE_SECTIONS, string]> = [
                ['props', 'Props'],
                ['parts', 'Parts'],
                ['slots', 'Slots'],
                ['events', 'Events'],
            ];
            for (const [key, text] of sections) {
                if (tables[key].length > 0) {
                    list.push({ id: TABLE_SECTIONS[key], text, level: 2 });
                }
            }
        }
        return list;
    }, [stackExamples, tables]);

    const activeName = component.stacks.find((stack) => stack.key === activeStack)?.name;

    return (
        <DocsLayout
            sidebar={<MainSidebar navigation={navigation} />}
            toc={<TableOfContents headings={headings} />}
        >
            <Head title={component.name}>
                <meta name="description" content={component.description} />
            </Head>

            <div className="mb-5 flex items-center gap-2 font-mono text-[13px] text-text-subtle">
                <span>Docs</span>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                <span>Design System</span>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                <span>Components</span>
                <i className="fa-solid fa-chevron-right text-[9px]" aria-hidden />
                <span className="text-text-muted">{component.name}</span>
            </div>

            <div className="mb-5 flex flex-wrap items-baseline gap-3">
                <h1 className="font-display text-[46px] font-extrabold leading-[1.1] tracking-[-0.02em] text-text">
                    {component.name}
                </h1>
                <span className="font-mono text-[12px] uppercase tracking-[0.14em] text-text-subtle">
                    {component.status}
                </span>
            </div>

            <p className="mb-10 max-w-[660px] text-[19px] leading-[1.6] text-text-muted">
                {component.description}
            </p>

            {activeStack ? (
                <>
                    <div className="mb-9 flex flex-wrap items-center gap-4">
                        <StackTabs
                            stacks={component.stacks}
                            active={activeStack}
                            onChange={setActiveStack}
                            idPrefix={TAB_ID_PREFIX}
                        />
                        {activeName ? (
                            <code className="font-mono text-[13px] text-text-subtle">
                                {activeStack === 'livewire' ? `<${activeName}>` : activeName}
                            </code>
                        ) : null}
                    </div>

                    <div
                        role="tabpanel"
                        id={stackPanelId(TAB_ID_PREFIX, activeStack)}
                        aria-labelledby={stackTabId(TAB_ID_PREFIX, activeStack)}
                        className="flex flex-col gap-16"
                    >
                        {stackExamples.length > 0 ? (
                            <section aria-labelledby="examples" className="flex flex-col gap-10">
                                <h2
                                    id="examples"
                                    className="scroll-mt-24 font-display text-[30px] font-bold tracking-[-0.01em] text-text"
                                >
                                    Examples
                                </h2>
                                {stackExamples.map((example) => (
                                    <Example
                                        key={`${activeStack}:${example.id}`}
                                        example={example}
                                        stack={activeStack}
                                        contentKey={`design-system/components/${component.slug}`}
                                    />
                                ))}
                            </section>
                        ) : (
                            <p className="text-text-subtle">
                                No {STACK_LABELS[activeStack]} examples yet.
                            </p>
                        )}

                        {tables ? <ManifestTables tables={tables} /> : null}
                    </div>
                </>
            ) : (
                <p className="text-text-subtle">This component does not ship in any stack yet.</p>
            )}
        </DocsLayout>
    );
}

/** The example's anchor id on the page. */
function exampleId(example: ComponentExample): string {
    return `example-${example.id}`;
}

/** One example: the live preview above its source in each stack. */
function Example({
    example,
    stack,
    contentKey,
}: {
    example: ComponentExample;
    stack: StackKey;
    contentKey: string;
}) {
    const source = example.sources[stack];
    if (!source) {
        return null;
    }

    return (
        <article aria-labelledby={exampleId(example)} className="flex flex-col gap-3">
            <h3
                id={exampleId(example)}
                className="scroll-mt-24 font-display text-[22px] font-bold text-text"
            >
                {example.title}
            </h3>
            <p className="max-w-[660px] text-[16px] leading-[1.7] text-text-muted">
                {example.description}
            </p>
            <div className="[&_pre]:rounded-t-none!">
                <ExamplePreview stack={stack} source={source} title={example.title} />
                <CodeBlock
                    code={source.code}
                    language={CODE_LANGUAGES[stack]}
                    label={`${example.title} (${STACK_LABELS[stack]})`}
                    contentKey={contentKey}
                />
            </div>
        </article>
    );
}
