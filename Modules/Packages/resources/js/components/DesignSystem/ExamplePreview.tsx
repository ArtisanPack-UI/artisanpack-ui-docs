import {
    createElement,
    useEffect,
    useRef,
    useState,
    useSyncExternalStore,
    type ComponentType,
} from 'react';
import { createRoot } from 'react-dom/client';
import type { Component as VueComponent } from 'vue';

import type { ExampleSource, StackKey } from './types';

type ExampleLoader<T> = () => Promise<{ default: T }>;

/*
 * The React and Vue examples live in the design-system package, next to
 * the manifest (`vendor/artisanpack-ui/design-system/packages/manifest/
 * examples`, aliased in vite.config.js). They're client-only islands:
 * the globs are lazy, mounted from an effect, and skipped entirely in the
 * SSR build (`import.meta.env.SSR` is a build-time constant, so Rollup
 * drops the dead branch and the SSR bundle never compiles or imports an
 * example, Vue, or `@artisanpack-ui/vue`).
 */
const reactExamples: Record<string, ExampleLoader<ComponentType>> = import.meta.env.SSR
    ? {}
    : import.meta.glob<{ default: ComponentType }>(
          '@artisanpack-ui/design-system/examples/*/*.tsx',
      );

const vueExamples: Record<string, ExampleLoader<VueComponent>> = import.meta.env.SSR
    ? {}
    : import.meta.glob<{ default: VueComponent }>('@artisanpack-ui/design-system/examples/*/*.vue');

/** Find the loader for `Button/basic.tsx`, whatever prefix Vite gave the glob key. */
function findLoader<T>(
    loaders: Record<string, ExampleLoader<T>>,
    file: string,
): ExampleLoader<T> | null {
    const suffix = `/${file}`;
    const key = Object.keys(loaders).find((candidate) => candidate.endsWith(suffix));
    return key ? loaders[key] : null;
}

export interface ExamplePreviewProps {
    stack: StackKey;
    source: ExampleSource;
    title: string;
}

/** The live preview for one example in one stack. */
export function ExamplePreview({ stack, source, title }: ExamplePreviewProps) {
    return (
        <div
            role="group"
            aria-label={`${title} preview`}
            className="flex min-h-[120px] flex-wrap items-center gap-3 rounded-t-[12px] border border-b-0 border-border-subtle bg-surface px-6 py-8"
        >
            {stack === 'livewire' ? (
                <BladePreview html={source.html ?? null} />
            ) : (
                <IslandPreview key={`${stack}:${source.file}`} stack={stack} file={source.file} />
            )}
        </div>
    );
}

/** The Blade example, rendered on the server and injected as HTML. */
function BladePreview({ html }: { html: string | null }) {
    if (html === null) {
        return <PreviewUnavailable />;
    }

    // Server-rendered from an example file in the design-system repo
    // (not user content), so it is injected as-is.
    return (
        <div
            className="flex flex-wrap items-center gap-3"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}

/** Shown when an example has no live preview, or it failed to mount. */
function PreviewUnavailable() {
    return (
        <p className="text-[14px] text-text-subtle">
            <i className="fa-solid fa-circle-info mr-2" aria-hidden />
            Preview unavailable on the docs site. The code below is the complete example.
        </p>
    );
}

type IslandStatus = 'loading' | 'ready' | 'error';

const subscribeNever = () => () => {};

/**
 * False on the server and during hydration, true afterwards. The example
 * globs are empty in the SSR build, so the island must render what the
 * server did (no loader) until hydration is done, or React reports a
 * hydration mismatch.
 */
function useHydrated(): boolean {
    return useSyncExternalStore(
        subscribeNever,
        () => true,
        () => false,
    );
}

/** Mounts a React or Vue example as an island after hydration. */
function IslandPreview({ stack, file }: { stack: 'react' | 'vue'; file: string }) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [status, setStatus] = useState<IslandStatus>('loading');
    const hydrated = useHydrated();
    const loader = !hydrated
        ? null
        : stack === 'react'
          ? findLoader(reactExamples, file)
          : findLoader(vueExamples, file);

    useEffect(() => {
        const container = containerRef.current;
        if (!container || !loader) {
            return;
        }

        let cancelled = false;
        let unmount: (() => void) | null = null;

        const mount = async () => {
            try {
                const example = await loader();
                if (cancelled) {
                    return;
                }

                if (stack === 'react') {
                    const root = createRoot(container, {
                        onUncaughtError: () => setStatus('error'),
                    });
                    root.render(createElement(example.default as ComponentType));
                    // Defer: unmounting a root while the page itself is
                    // rendering (an effect cleanup) makes React warn.
                    unmount = () => window.setTimeout(() => root.unmount(), 0);
                } else {
                    const { createApp } = await import('vue');
                    if (cancelled) {
                        return;
                    }
                    const app = createApp(example.default as VueComponent);
                    app.config.errorHandler = () => setStatus('error');
                    app.mount(container);
                    unmount = () => app.unmount();
                }

                setStatus('ready');
            } catch (error) {
                console.error(`Could not mount the ${stack} example ${file}`, error);
                if (!cancelled) {
                    setStatus('error');
                }
            }
        };

        void mount();

        return () => {
            cancelled = true;
            unmount?.();
        };
    }, [loader, stack, file]);

    // Before hydration there's no loader yet: show loading, not "unavailable",
    // so the server HTML and the first client render match and don't mislead.
    const unavailable = (hydrated && !loader) || status === 'error';

    return (
        <>
            {unavailable ? <PreviewUnavailable /> : null}
            {!unavailable && status === 'loading' ? (
                <span className="text-[14px] text-text-subtle">Loading preview…</span>
            ) : null}
            {/* Kept mounted so the island's root never loses its node.
                React never renders children into it; the island owns it. */}
            <div
                ref={containerRef}
                hidden={unavailable}
                className="flex flex-wrap items-center gap-3"
            />
        </>
    );
}
