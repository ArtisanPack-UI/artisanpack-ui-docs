import type { ReactNode } from 'react';

import { TABLE_SECTIONS, type ManifestTables as Tables } from './types';

export interface ManifestTablesProps {
    tables: Tables;
}

/**
 * The props, parts, slots and events tables for the active stack, as
 * generated from the manifest by `ManifestDocs::tables()`. Empty tables
 * are left out.
 */
export function ManifestTables({ tables }: ManifestTablesProps) {
    return (
        <div className="flex flex-col gap-12">
            {tables.props.length > 0 ? (
                <Section id={TABLE_SECTIONS.props} title="Props">
                    <Table headers={['Name', 'Type', 'Default', 'Description']}>
                        {tables.props.map((prop) => (
                            <tr key={prop.name}>
                                <Cell>
                                    <Code>{prop.name}</Code>
                                    {prop.required ? (
                                        <WarningBadge className="ml-2">Required</WarningBadge>
                                    ) : null}
                                </Cell>
                                <Cell>
                                    <Code>{prop.typeLabel}</Code>
                                </Cell>
                                <Cell>
                                    {prop.default === null || prop.default === undefined ? (
                                        <span className="text-text-subtle">–</span>
                                    ) : (
                                        <Code>{formatDefault(prop.default)}</Code>
                                    )}
                                </Cell>
                                <Cell>
                                    <p>{prop.description}</p>
                                    {prop.a11y ? (
                                        <p className="mt-1 text-text-subtle">
                                            <span className="font-semibold text-text-muted">
                                                Accessibility:
                                            </span>{' '}
                                            {prop.a11y}
                                        </p>
                                    ) : null}
                                    {prop.deprecated ? (
                                        <p className="mt-1">
                                            <WarningBadge className="mr-2">Deprecated</WarningBadge>
                                            {prop.deprecated}
                                        </p>
                                    ) : null}
                                </Cell>
                            </tr>
                        ))}
                    </Table>
                </Section>
            ) : null}

            {tables.parts.length > 0 ? (
                <Section id={TABLE_SECTIONS.parts} title="Parts">
                    <Table headers={['Name', 'Element', 'Description']}>
                        {tables.parts.map((part) => (
                            <tr key={part.name}>
                                <Cell>
                                    <Code>{part.name}</Code>
                                </Cell>
                                <Cell>
                                    {part.element ? (
                                        <Code>{part.element}</Code>
                                    ) : (
                                        <span className="text-text-subtle">–</span>
                                    )}
                                </Cell>
                                <Cell>{part.description}</Cell>
                            </tr>
                        ))}
                    </Table>
                </Section>
            ) : null}

            {tables.slots.length > 0 ? (
                <Section id={TABLE_SECTIONS.slots} title="Slots">
                    <Table headers={['Name', 'Description']}>
                        {tables.slots.map((slot) => (
                            <tr key={slot.name}>
                                <Cell>
                                    <Code>{slot.name}</Code>
                                    {slot.scoped ? (
                                        <span className="ml-2 font-mono text-[11px] uppercase tracking-[0.12em] text-text-subtle">
                                            Scoped
                                        </span>
                                    ) : null}
                                </Cell>
                                <Cell>
                                    <p>{slot.description}</p>
                                    {slot.parameters.length > 0 ? (
                                        <p className="mt-1 text-text-subtle">
                                            Parameters:{' '}
                                            {slot.parameters.map((parameter, index) => (
                                                <span key={parameter}>
                                                    {index > 0 ? ', ' : null}
                                                    <Code>{parameter}</Code>
                                                </span>
                                            ))}
                                        </p>
                                    ) : null}
                                </Cell>
                            </tr>
                        ))}
                    </Table>
                </Section>
            ) : null}

            {tables.events.length > 0 ? (
                <Section id={TABLE_SECTIONS.events} title="Events">
                    <Table headers={['Name', 'Payload', 'Description']}>
                        {tables.events.map((event) => (
                            <tr key={event.concept}>
                                <Cell>
                                    <Code>{event.name}</Code>
                                </Cell>
                                <Cell>
                                    {event.payload ? (
                                        <Code>{event.payload}</Code>
                                    ) : (
                                        <span className="text-text-subtle">–</span>
                                    )}
                                </Cell>
                                <Cell>{event.description}</Cell>
                            </tr>
                        ))}
                    </Table>
                </Section>
            ) : null}
        </div>
    );
}

/**
 * An amber label. The amber is a fill with ink text rather than amber
 * text: `text-warning` on the light theme's white surface is about
 * 2.1:1, while ink on amber is about 8.5:1 in both themes.
 */
function WarningBadge({ className = '', children }: { className?: string; children: ReactNode }) {
    return (
        <span
            className={`${className} inline-flex items-center rounded-full bg-warning px-2 py-0.5 font-mono text-[11px] font-bold uppercase tracking-[0.12em] text-ink`}
        >
            {children}
        </span>
    );
}

/** A prop's default value as the tables print it. */
function formatDefault(value: unknown): string {
    if (typeof value === 'string') {
        return `'${value}'`;
    }
    return JSON.stringify(value);
}

/** A titled, linkable section of the API reference. */
function Section({ id, title, children }: { id: string; title: string; children: ReactNode }) {
    return (
        <section aria-labelledby={id} className="flex flex-col gap-4">
            <h2
                id={id}
                className="scroll-mt-24 font-display text-[30px] font-bold tracking-[-0.01em] text-text"
            >
                {title}
            </h2>
            {children}
        </section>
    );
}

/** A table with the given column headers. */
function Table({ headers, children }: { headers: string[]; children: ReactNode }) {
    return (
        <div className="overflow-x-auto rounded-[12px] border border-border-subtle">
            <table className="w-full border-collapse text-left text-[15px]">
                <thead className="bg-surface-2">
                    <tr>
                        {headers.map((header) => (
                            <th
                                key={header}
                                scope="col"
                                className="border-b border-border-subtle px-4 py-3 font-mono text-[11px] font-normal uppercase tracking-[0.14em] text-text-subtle"
                            >
                                {header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-border-subtle">{children}</tbody>
            </table>
        </div>
    );
}

/** A body cell. */
function Cell({ children }: { children: ReactNode }) {
    return <td className="px-4 py-3 align-top leading-[1.6] text-text-muted">{children}</td>;
}

/** Inline code. */
function Code({ children }: { children: ReactNode }) {
    return (
        <code className="rounded-[4px] bg-surface-2 px-1.5 py-0.5 font-mono text-[13px] text-text">
            {children}
        </code>
    );
}
