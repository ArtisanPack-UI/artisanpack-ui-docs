import { router } from '@inertiajs/react';
import { useCallback, useEffect, useId, useMemo, useRef, useState } from 'react';

import { trackDocsEvent } from '../lib/analytics';

export type SearchOverlayIcon = { type: 'svg'; markup: string } | { type: 'class'; class: string };

export interface SearchOverlayResult {
    id: string;
    name: string;
    description: string;
    link: string;
    icon: SearchOverlayIcon | null;
}

export interface SearchOverlayProps {
    onClose: () => void;
    endpoint?: string;
    debounceMs?: number;
}

const DEFAULT_ENDPOINT = '/search';
const DEFAULT_DEBOUNCE_MS = 250;

/**
 * Modal command palette that reproduces the legacy `mary-search-open`
 * behavior against the same underlying data source (pages, packages,
 * docs, changelogs) via the JSON `/search` endpoint.
 *
 * Mounted only when the overlay is open — the parent is expected to
 * conditionally render this component so each opening starts from a
 * fresh state without any explicit reset effect.
 */
export function SearchOverlay({
    onClose,
    endpoint = DEFAULT_ENDPOINT,
    debounceMs = DEFAULT_DEBOUNCE_MS,
}: SearchOverlayProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchOverlayResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [searched, setSearched] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);

    const inputRef = useRef<HTMLInputElement>(null);
    const listRef = useRef<HTMLUListElement>(null);
    const abortRef = useRef<AbortController | null>(null);
    const titleId = useId();

    const trimmed = useMemo(() => query.trim(), [query]);

    const close = useCallback(() => {
        abortRef.current?.abort();
        abortRef.current = null;
        onClose();
    }, [onClose]);

    useEffect(() => {
        const id = window.requestAnimationFrame(() => {
            inputRef.current?.focus();
        });
        return () => window.cancelAnimationFrame(id);
    }, []);

    useEffect(() => {
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = previousOverflow;
        };
    }, []);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        };
        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [close]);

    useEffect(() => {
        if (trimmed === '') {
            return;
        }

        const timer = window.setTimeout(() => {
            abortRef.current?.abort();
            const controller = new AbortController();
            abortRef.current = controller;
            setLoading(true);

            const url = `${endpoint}?q=${encodeURIComponent(trimmed)}`;
            fetch(url, {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then(async (response) => {
                    if (!response.ok) {
                        throw new Error(`Search request failed: ${response.status}`);
                    }
                    const payload = (await response.json()) as {
                        results?: SearchOverlayResult[];
                    };
                    // `abort()` cannot cancel a fetch whose response
                    // already landed but hasn't run its `.then` yet, so
                    // guard against a superseded controller writing stale
                    // results over a newer query.
                    if (abortRef.current !== controller) {
                        return;
                    }
                    const nextResults = payload.results ?? [];
                    setResults(nextResults);
                    setSearched(true);
                    setActiveIndex(0);
                    // Fire per issue #100 requirement: docs search events.
                    // Queries and result counts inform which docs pages
                    // and packages are being discovered vs. missed.
                    trackDocsEvent(
                        'docs.search',
                        {
                            query: trimmed,
                            results: nextResults.length,
                        },
                        { category: 'docs' },
                    );
                })
                .catch((error) => {
                    if (error instanceof DOMException && error.name === 'AbortError') {
                        return;
                    }
                    if (abortRef.current !== controller) {
                        return;
                    }
                    setResults([]);
                    setSearched(true);
                })
                .finally(() => {
                    if (abortRef.current === controller) {
                        setLoading(false);
                        abortRef.current = null;
                    }
                });
        }, debounceMs);

        return () => window.clearTimeout(timer);
    }, [trimmed, endpoint, debounceMs]);

    const onQueryChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const next = event.target.value;
        setQuery(next);
        if (next.trim() === '') {
            abortRef.current?.abort();
            abortRef.current = null;
            setResults([]);
            setSearched(false);
            setLoading(false);
        }
    };

    useEffect(
        () => () => {
            abortRef.current?.abort();
            abortRef.current = null;
        },
        [],
    );

    const navigate = useCallback(
        (result: SearchOverlayResult) => {
            trackDocsEvent(
                'docs.search_result_open',
                {
                    query: trimmed,
                    link: result.link,
                    result_id: result.id,
                },
                { category: 'docs' },
            );
            close();
            router.visit(result.link);
        },
        [close, trimmed],
    );

    const onInputKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'ArrowDown') {
            if (results.length === 0) {
                return;
            }
            event.preventDefault();
            setActiveIndex((index) => (index + 1) % results.length);
            return;
        }
        if (event.key === 'ArrowUp') {
            if (results.length === 0) {
                return;
            }
            event.preventDefault();
            setActiveIndex((index) => (index - 1 + results.length) % results.length);
            return;
        }
        if (event.key === 'Enter') {
            const target = results[activeIndex];
            if (!target) {
                return;
            }
            event.preventDefault();
            navigate(target);
        }
    };

    useEffect(() => {
        if (results.length === 0) {
            return;
        }
        const list = listRef.current;
        if (!list) {
            return;
        }
        const active = list.querySelector<HTMLElement>(`[data-search-index="${activeIndex}"]`);
        active?.scrollIntoView({ block: 'nearest' });
    }, [activeIndex, results.length]);

    const showEmpty = searched && !loading && results.length === 0 && trimmed !== '';
    const showPrompt = trimmed === '' && !loading;

    return (
        <div
            className="fixed inset-0 z-[70] flex items-start justify-center px-4 pt-[12vh]"
            role="dialog"
            aria-modal="true"
            aria-labelledby={titleId}
        >
            <button
                type="button"
                aria-label="Close search"
                tabIndex={-1}
                onClick={close}
                className="absolute inset-0 bg-black/60 backdrop-blur-sm"
            />
            <div
                className="relative w-full max-w-2xl overflow-hidden rounded-[14px] border border-border-subtle bg-surface shadow-2xl"
                style={{
                    background: 'linear-gradient(180deg, #080C16 0%, #05070E 100%)',
                }}
            >
                <div className="flex items-center gap-3 border-b border-border-subtle px-5 py-4">
                    <i
                        className="fa-solid fa-magnifying-glass text-[15px] text-text-subtle"
                        aria-hidden
                    />
                    <input
                        ref={inputRef}
                        id={titleId}
                        type="search"
                        value={query}
                        onChange={onQueryChange}
                        onKeyDown={onInputKeyDown}
                        placeholder="Search the docs…"
                        aria-label="Search query"
                        aria-autocomplete="list"
                        aria-controls={`${titleId}-results`}
                        autoComplete="off"
                        spellCheck={false}
                        className="min-w-0 flex-1 bg-transparent text-[16px] text-text placeholder:text-text-subtle focus:outline-none"
                    />
                    {loading ? (
                        <span
                            className="inline-flex h-4 w-4 animate-spin rounded-full border-2 border-border-subtle border-t-secondary"
                            aria-label="Searching"
                            role="status"
                        />
                    ) : (
                        <kbd className="rounded-[5px] border border-border-subtle bg-surface-2 px-[7px] py-[2px] font-mono text-[11px] text-text-muted">
                            Esc
                        </kbd>
                    )}
                </div>

                {showPrompt ? (
                    <p className="px-5 py-6 text-small text-text-subtle">
                        Type to search across pages, packages, docs, and changelogs.
                    </p>
                ) : null}

                {showEmpty ? (
                    <p className="px-5 py-6 text-small text-text-subtle">
                        No matches for “{trimmed}”.
                    </p>
                ) : null}

                {results.length > 0 ? (
                    <ul
                        ref={listRef}
                        id={`${titleId}-results`}
                        role="listbox"
                        aria-label="Search results"
                        className="max-h-[60vh] overflow-y-auto py-1"
                    >
                        {results.map((result, index) => (
                            <li key={result.id} role="none">
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected={index === activeIndex}
                                    data-search-index={index}
                                    onClick={() => navigate(result)}
                                    onMouseEnter={() => setActiveIndex(index)}
                                    className={`flex w-full items-center gap-3 px-5 py-3 text-left transition ${
                                        index === activeIndex
                                            ? 'bg-white/[0.06] text-text'
                                            : 'text-text-muted hover:bg-white/[0.03] hover:text-text'
                                    }`}
                                >
                                    <ResultIcon icon={result.icon} />
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-[15px] font-semibold text-text">
                                            {result.name}
                                        </span>
                                        <span className="block truncate text-[12px] text-text-subtle">
                                            {result.description}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                ) : null}
            </div>
        </div>
    );
}

function ResultIcon({ icon }: { icon: SearchOverlayIcon | null }) {
    if (icon?.type === 'svg') {
        return (
            <span
                aria-hidden
                className="inline-flex h-5 w-5 items-center justify-center text-text-subtle"
                dangerouslySetInnerHTML={{ __html: sizedSvg(icon.markup, 18) }}
            />
        );
    }

    const className = icon?.type === 'class' ? icon.class : 'fa-solid fa-file-lines';

    return (
        <i
            aria-hidden
            className={`${className} inline-flex h-5 w-5 items-center justify-center text-[15px] text-text-subtle`}
        />
    );
}

function sizedSvg(markup: string, size: number): string {
    if (/<svg\b[^>]*\swidth=/i.test(markup)) {
        return markup;
    }
    return markup.replace(/<svg\b/i, `<svg width="${size}" height="${size}"`);
}

export default SearchOverlay;
