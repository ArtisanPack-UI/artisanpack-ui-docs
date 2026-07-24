import { useEffect, type RefObject } from 'react';

/**
 * Injects a "Copy" button into every `.code-block-container` inside the
 * referenced article.
 *
 * The docs and changelog viewers render kses-sanitized HTML into a
 * `dangerouslySetInnerHTML` article, so we can't render React inside the
 * code blocks directly. Instead, we run once per content string, walk
 * the article for the containers produced by TableOfContentsService, and
 * append a native `<button>` that writes the block's text to the
 * clipboard. Re-running the effect when `contentKey` changes handles
 * Inertia navigations that swap the article in place without unmounting
 * the page component.
 */
export function useCopyableCodeBlocks(
    articleRef: RefObject<HTMLElement | null>,
    contentKey: string,
): void {
    useEffect(() => {
        const article = articleRef.current;
        if (!article || typeof window === 'undefined') {
            return;
        }

        const containers = article.querySelectorAll<HTMLDivElement>(
            '.code-block-container',
        );
        const cleanups: Array<() => void> = [];

        containers.forEach((container) => {
            if (container.querySelector('[data-copy-button]')) {
                return;
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.copyButton = 'true';
            button.setAttribute('aria-label', 'Copy code to clipboard');
            button.className =
                'absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-[7px] border border-border-subtle bg-surface-2/80 px-2.5 py-1 font-mono text-[11px] uppercase tracking-[0.12em] text-text-subtle backdrop-blur transition hover:border-border hover:text-text';
            button.textContent = 'Copy';

            container.classList.add('relative');
            container.appendChild(button);

            const handler = async () => {
                const code = container.querySelector('code');
                if (!code) {
                    return;
                }
                try {
                    await navigator.clipboard.writeText(code.textContent ?? '');
                    button.textContent = 'Copied';
                    button.classList.add('text-text');
                    window.setTimeout(() => {
                        button.textContent = 'Copy';
                        button.classList.remove('text-text');
                    }, 1500);
                } catch {
                    button.textContent = 'Failed';
                    window.setTimeout(() => {
                        button.textContent = 'Copy';
                    }, 1500);
                }
            };

            button.addEventListener('click', handler);
            cleanups.push(() => {
                button.removeEventListener('click', handler);
                button.remove();
            });
        });

        return () => {
            for (const cleanup of cleanups) {
                cleanup();
            }
        };
    }, [articleRef, contentKey]);
}
