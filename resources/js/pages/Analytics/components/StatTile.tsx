import type { ReactNode } from 'react';

export interface StatTileProps {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    trend?: {
        change: number;
        positive?: boolean;
    } | null;
}

function formatChange(change: number): string {
    const sign = change > 0 ? '+' : '';
    return `${sign}${change.toFixed(1)}%`;
}

export function StatTile({ label, value, hint, trend }: StatTileProps) {
    const trendClass = trend
        ? trend.positive === false
            ? 'text-error'
            : trend.change > 0
              ? 'text-success'
              : trend.change < 0
                ? 'text-error'
                : 'text-text-muted'
        : 'text-text-muted';

    return (
        <div className="flex flex-col gap-2 rounded-box border border-border-subtle bg-surface-2 p-5">
            <span className="text-xsmall font-medium uppercase tracking-[0.12em] text-text-subtle">
                {label}
            </span>
            <span className="font-display text-h4 leading-none tabular-nums text-text">
                {value}
            </span>
            {trend || hint ? (
                <span className="flex items-center gap-2 text-xsmall text-text-muted">
                    {trend ? (
                        <span className={`tabular-nums font-medium ${trendClass}`}>
                            {formatChange(trend.change)}
                        </span>
                    ) : null}
                    {hint ? <span>{hint}</span> : null}
                </span>
            ) : null}
        </div>
    );
}
