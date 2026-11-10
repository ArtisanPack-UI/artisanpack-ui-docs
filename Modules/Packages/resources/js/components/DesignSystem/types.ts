/**
 * Prop shapes for the design-system component page (design-system#43).
 *
 * Mirrors `ComponentDocsController` and `ManifestDocs::tables()` in the
 * `artisanpack-ui/design-system` package.
 */

/** Manifest stack keys. `livewire` is the Blade tab. */
export type StackKey = 'livewire' | 'react' | 'vue';

export interface ManifestProp {
    name: string;
    type: string;
    typeLabel: string;
    values: string[];
    default: unknown;
    required: boolean;
    description: string;
    a11y: string | null;
    deprecated: string | null;
}

export interface ManifestPart {
    name: string;
    element?: string;
    description: string;
}

export interface ManifestSlot {
    name: string;
    description: string;
    scoped: boolean;
    parameters: string[];
}

export interface ManifestEvent {
    concept: string;
    name: string;
    description: string;
    payload: string | null;
}

export interface ManifestTables {
    stack: StackKey;
    props: ManifestProp[];
    parts: ManifestPart[];
    slots: ManifestSlot[];
    events: ManifestEvent[];
}

export interface ExampleSource {
    /** Path relative to the design-system examples directory, such as `Button/basic.tsx`. */
    file: string;
    code: string;
    /** Server-rendered Blade preview; `null` when it couldn't render here. */
    html?: string | null;
}

export interface ComponentExample {
    id: string;
    title: string;
    description: string;
    sources: Partial<Record<StackKey, ExampleSource>>;
}

export interface ComponentStack {
    key: StackKey;
    /** How the component is written in that stack, such as `x-artisanpack-button` or `Button`. */
    name: string;
}

export const STACK_LABELS: Record<StackKey, string> = {
    livewire: 'Blade',
    react: 'React',
    vue: 'Vue',
};

export const stackTabId = (prefix: string, stack: StackKey) => `${prefix}-tab-${stack}`;
export const stackPanelId = (prefix: string, stack: StackKey) => `${prefix}-panel-${stack}`;

/** Section ids for the manifest tables, shared with the page's table of contents. */
export const TABLE_SECTIONS = {
    props: 'props',
    parts: 'parts',
    slots: 'slots',
    events: 'events',
} as const;
