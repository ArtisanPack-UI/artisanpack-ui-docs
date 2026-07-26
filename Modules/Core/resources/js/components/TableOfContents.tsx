import { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';

import type { TocHeading } from '../types/navigation';

export interface TableOfContentsProps {
    headings: TocHeading[];
    /**
     * CSS selector for the article root that holds the headings. All
     * `h1`–`h6` elements with `id` attributes inside this container are
     * observed for scroll-spy.
     */
    contentSelector?: string;
}

export function TableOfContents({ headings, contentSelector = '#main' }: TableOfContentsProps) {
    const [activeId, setActiveId] = useState<string | null>(null);
    const observerRef = useRef<IntersectionObserver | null>(null);
    const listRef = useRef<HTMLUListElement>(null);
    const railRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (headings.length === 0 || typeof window === 'undefined') {
            return;
        }

        const container = document.querySelector(contentSelector);
        if (!container) {
            return;
        }

        const headingElements = container.querySelectorAll<HTMLElement>(
            'h1[id], h2[id], h3[id], h4[id], h5[id], h6[id]',
        );

        if (headingElements.length === 0) {
            return;
        }

        observerRef.current?.disconnect();

        observerRef.current = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('id');
                        if (id) {
                            setActiveId(id);
                        }
                    }
                }
            },
            {
                rootMargin: '-100px 0px -66%',
                threshold: 0,
            },
        );

        for (const el of headingElements) {
            observerRef.current.observe(el);
        }

        return () => {
            observerRef.current?.disconnect();
            observerRef.current = null;
        };
    }, [headings, contentSelector]);

    useLayoutEffect(() => {
        if (!activeId || !listRef.current || !railRef.current) {
            return;
        }
        const active = listRef.current.querySelector<HTMLAnchorElement>(
            `a[data-target="${CSS.escape(activeId)}"]`,
        );
        if (!active) {
            return;
        }
        const listBox = listRef.current.getBoundingClientRect();
        const linkBox = active.getBoundingClientRect();
        const top = Math.max(0, linkBox.top - listBox.top);
        const height = Math.min(listBox.height - top, linkBox.top - listBox.top + linkBox.height);
        railRef.current.style.setProperty('--toc-progress-top', `${top}px`);
        railRef.current.style.setProperty('--toc-progress-height', `${Math.max(24, height)}px`);
    }, [activeId]);

    const handleClick = useCallback(
        (event: React.MouseEvent<HTMLAnchorElement>, targetId: string) => {
            event.preventDefault();
            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.history.pushState(null, '', `#${targetId}`);
        },
        [],
    );

    if (headings.length === 0) {
        return null;
    }

    return (
        <nav aria-label="Table of contents">
            <div className="mb-4 font-mono text-[11px] uppercase tracking-[0.16em] text-text-subtle">
                On this page
            </div>
            <div className="flex gap-3.5">
                <div
                    ref={railRef}
                    className="relative w-[2px] flex-shrink-0 rounded"
                    style={{ background: 'rgba(255,255,255,0.08)' }}
                    aria-hidden
                >
                    <div
                        className="absolute left-0 w-[2px] rounded transition-[top,height] duration-200"
                        style={{
                            top: 'var(--toc-progress-top, 0px)',
                            height: 'var(--toc-progress-height, 0px)',
                            background: 'var(--grad-neon)',
                        }}
                    />
                </div>
                <ul ref={listRef} className="flex flex-col gap-3">
                    {headings.map((heading) => (
                        <TocItem
                            key={heading.id}
                            heading={heading}
                            activeId={activeId}
                            onSelect={handleClick}
                        />
                    ))}
                </ul>
            </div>
        </nav>
    );
}

function TocItem({
    heading,
    activeId,
    onSelect,
    depth = 0,
}: {
    heading: TocHeading;
    activeId: string | null;
    onSelect: (event: React.MouseEvent<HTMLAnchorElement>, targetId: string) => void;
    depth?: number;
}) {
    const isActive = activeId === heading.id;
    const indent = depth === 0 ? '' : depth === 1 ? 'pl-3' : 'pl-6';

    return (
        <li>
            <a
                href={`#${heading.id}`}
                data-target={heading.id}
                onClick={(event) => onSelect(event, heading.id)}
                className={`block text-[14px] transition-colors ${indent} ${
                    isActive ? 'font-semibold text-text' : 'text-text-muted hover:text-text'
                }`}
            >
                {heading.text}
            </a>
            {heading.children && heading.children.length > 0 ? (
                <ul className="mt-2 flex flex-col gap-2">
                    {heading.children.map((child) => (
                        <TocItem
                            key={child.id}
                            heading={child}
                            activeId={activeId}
                            onSelect={onSelect}
                            depth={depth + 1}
                        />
                    ))}
                </ul>
            ) : null}
        </li>
    );
}

export default TableOfContents;
