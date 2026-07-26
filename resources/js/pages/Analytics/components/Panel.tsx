import type { CSSProperties, ReactNode } from 'react';

export interface PanelProps {
    title?: string;
    description?: string;
    actions?: ReactNode;
    children: ReactNode;
    padded?: boolean;
    className?: string;
    /**
     * When true, the outer frame gets a neon gradient border (same
     * `--grad-neon` token as the docs layout header hairline) via the
     * border-box/padding-box layering trick. Reserved for headline
     * panels — using it on every card would make the accent lose
     * meaning.
     */
    accent?: boolean;
}

/**
 * Flat bordered container matching the packages/pages admin aesthetic.
 * No shadow, single subtle border, `bg-surface-2` fill. Kept as an
 * explicit local component instead of `@artisanpack-ui/react/layout`'s
 * `Card` because the DaisyUI card ships with a shadow and rounded-lg
 * radius that clashes with the rest of the admin surfaces.
 *
 * The `accent` variant swaps the flat border for a gradient one drawn
 * with border-box/padding-box layering — same technique the
 * `.ap-border-gradient` utility uses, inlined so the padding-box fill
 * matches `bg-surface-2` (the utility hardcodes `--color-surface`).
 */
export function Panel({
    title,
    description,
    actions,
    children,
    padded = true,
    className = '',
    accent = false,
}: PanelProps) {
    const hasHeader = Boolean(title || description || actions);

    const frameClass = accent
        ? `rounded-box border border-transparent ${className}`.trim()
        : `rounded-box border border-border-subtle bg-surface-2 ${className}`.trim();

    const frameStyle: CSSProperties | undefined = accent
        ? {
              background:
                  'linear-gradient(var(--color-surface-2), var(--color-surface-2)) padding-box, var(--grad-neon) border-box',
          }
        : undefined;

    return (
        <section className={frameClass} style={frameStyle}>
            {hasHeader ? (
                <header className="flex items-start justify-between gap-4 border-b border-border-subtle px-6 py-4">
                    <div className="flex flex-col gap-1">
                        {title ? <h2 className="font-display text-h6 text-text">{title}</h2> : null}
                        {description ? (
                            <p className="text-small text-text-muted">{description}</p>
                        ) : null}
                    </div>
                    {actions ? <div className="flex shrink-0 gap-2">{actions}</div> : null}
                </header>
            ) : null}
            <div className={padded ? 'p-6' : ''}>{children}</div>
        </section>
    );
}
