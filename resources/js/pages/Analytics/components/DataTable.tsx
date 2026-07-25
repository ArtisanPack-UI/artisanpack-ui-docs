import type { ReactNode } from 'react';

export interface DataTableColumn<T> {
    key: string;
    label: ReactNode;
    align?: 'left' | 'right';
    render: ( row: T ) => ReactNode;
    width?: string;
}

export interface DataTableProps<T> {
    rows: T[];
    columns: DataTableColumn<T>[];
    getRowKey: ( row: T, index: number ) => string;
    emptyText?: ReactNode;
}

/**
 * Flat, borderless table for the analytics widgets. Rows separated by
 * a hairline `border-border-subtle` divider; the enclosing `<Panel>` is
 * responsible for the outer frame so tables sit flush inside it.
 */
export function DataTable<T>( {
    rows,
    columns,
    getRowKey,
    emptyText = 'No data yet for the selected range.',
}: DataTableProps<T> ) {
    if ( rows.length === 0 ) {
        return <p className="text-small text-text-muted">{emptyText}</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-small">
                <thead>
                    <tr className="border-b border-border-subtle text-xsmall uppercase tracking-[0.08em] text-text-subtle">
                        {columns.map( ( column ) => (
                            <th
                                key={column.key}
                                className={`py-3 font-medium ${
                                    column.align === 'right' ? 'text-right' : 'text-left'
                                }`}
                                style={column.width ? { width: column.width } : undefined}
                            >
                                {column.label}
                            </th>
                        ) )}
                    </tr>
                </thead>
                <tbody>
                    {rows.map( ( row, index ) => (
                        <tr
                            key={getRowKey( row, index )}
                            className="border-b border-border-subtle/50 last:border-b-0"
                        >
                            {columns.map( ( column ) => (
                                <td
                                    key={column.key}
                                    className={`py-3 ${
                                        column.align === 'right'
                                            ? 'text-right tabular-nums'
                                            : 'text-left'
                                    }`}
                                >
                                    {column.render( row )}
                                </td>
                            ) )}
                        </tr>
                    ) )}
                </tbody>
            </table>
        </div>
    );
}
