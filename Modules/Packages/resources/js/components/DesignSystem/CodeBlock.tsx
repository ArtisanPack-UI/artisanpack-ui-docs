import { useEffect, useRef, useState } from 'react';

import { trackDocsEvent } from '@/lib/analytics';

export interface CodeBlockProps {
    code: string;
    /** Shown in the header and used for the `language-*` class, such as `blade`. */
    language: string;
    /** Accessible name for the copy button's context, such as the example title. */
    label: string;
    /** The page that owns the block, for analytics: `design-system/components/button`. */
    contentKey: string;
}

/**
 * An example's source, styled like the docs viewer's code blocks, with a
 * copy button. Copies fire the same `docs.code_copy` event as
 * `useCopyableCodeBlocks`.
 */
export function CodeBlock({ code, language, label, contentKey }: CodeBlockProps) {
    const [copied, setCopied] = useState(false);
    const resetTimer = useRef<number | null>(null);

    useEffect(
        () => () => {
            if (resetTimer.current !== null) {
                window.clearTimeout(resetTimer.current);
            }
        },
        [],
    );

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(code);
            setCopied(true);
            trackDocsEvent(
                'docs.code_copy',
                { content_key: contentKey, length: code.length, language },
                { category: 'docs' },
            );
            if (resetTimer.current !== null) {
                window.clearTimeout(resetTimer.current);
            }
            resetTimer.current = window.setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    };

    return (
        <div className="docs-prose relative">
            <button
                type="button"
                onClick={copy}
                aria-label={`Copy ${label} code to clipboard`}
                className="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-[7px] border border-border-subtle bg-surface-2/80 px-2.5 py-1 font-mono text-[11px] uppercase tracking-[0.12em] text-text-subtle backdrop-blur transition hover:border-border hover:text-text"
            >
                {copied ? 'Copied' : 'Copy'}
            </button>
            <span className="sr-only" aria-live="polite">
                {copied ? 'Copied to clipboard' : ''}
            </span>
            <pre>
                {/* The block background is always dark, so pin the text light
                    (the shared `.docs-prose code` color flips to black in
                    the light theme). */}
                <code className={`language-${language} text-[#E5E7EB]!`}>
                    {code.replace(/\n$/, '')}
                </code>
            </pre>
        </div>
    );
}
