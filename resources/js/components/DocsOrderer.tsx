import { useCallback, useMemo, useState } from 'react';
import type { DragEvent } from 'react';

export interface DocsOrdererItem {
    id: number;
    title: string;
    slug: string;
    parent: number;
    menu_order: number;
}

export interface DocsOrdererChangeItem {
    id: number;
    menu_order: number;
    [key: string]: number;
}

export interface DocsOrdererProps {
    items: DocsOrdererItem[];
    onChange: (items: DocsOrdererChangeItem[]) => void;
    emptyText?: string;
}

interface FlatRow extends DocsOrdererItem {
    depth: number;
}

/**
 * Groups the entries by their sortable "list" — one for roots (`parent === 0`)
 * and one per parent id for that parent's children. Each list sorts by
 * `menu_order`, then id as a tie-break.
 */
function buildLists(items: DocsOrdererItem[]): Map<number, DocsOrdererItem[]> {
    const lists = new Map<number, DocsOrdererItem[]>();
    items.forEach((item) => {
        const parent = item.parent ?? 0;
        const bucket = lists.get(parent) ?? [];
        bucket.push(item);
        lists.set(parent, bucket);
    });
    lists.forEach((bucket) => {
        bucket.sort((a, b) => a.menu_order - b.menu_order || a.id - b.id);
    });
    return lists;
}

/**
 * Renders the lists as one continuous flat sequence: each root is followed
 * immediately by its (indented) children before the next root.
 */
function toFlatRows(lists: Map<number, DocsOrdererItem[]>): FlatRow[] {
    const roots = lists.get(0) ?? [];
    const rows: FlatRow[] = [];
    roots.forEach((root) => {
        rows.push({ ...root, depth: 0 });
        (lists.get(root.id) ?? []).forEach((child) => {
            rows.push({ ...child, depth: 1 });
        });
    });
    return rows;
}

function toChangeList(lists: Map<number, DocsOrdererItem[]>): DocsOrdererChangeItem[] {
    const out: DocsOrdererChangeItem[] = [];
    lists.forEach((bucket) => {
        bucket.forEach((entry, index) => {
            out.push({ id: entry.id, menu_order: index });
        });
    });
    return out;
}

/**
 * Single-list drag-and-drop orderer. Every doc renders in one continuous
 * list — children are shown indented directly under their parent. Drag is
 * scoped to same-level entries (root vs. same-parent children); a top-level
 * page can't be dropped onto a child and vice versa.
 */
export function DocsOrderer({ items, onChange, emptyText = 'No documentation entries yet.' }: DocsOrdererProps) {
    const itemsSignature = useMemo(
        () => items.map((item) => `${item.id}:${item.parent}:${item.menu_order}`).join('|'),
        [items],
    );

    const [lists, setLists] = useState<Map<number, DocsOrdererItem[]>>(() => buildLists(items));
    const [prevSignature, setPrevSignature] = useState<string>(itemsSignature);
    if (prevSignature !== itemsSignature) {
        setPrevSignature(itemsSignature);
        setLists(buildLists(items));
    }

    const [dragging, setDragging] = useState<{ parent: number; id: number } | null>(null);
    const [dragTarget, setDragTarget] = useState<{ parent: number; id: number } | null>(null);

    const rows = useMemo(() => toFlatRows(lists), [lists]);

    const commit = useCallback(
        (next: Map<number, DocsOrdererItem[]>) => {
            setLists(next);
            onChange(toChangeList(next));
        },
        [onChange],
    );

    const move = useCallback(
        (parent: number, sourceId: number, targetId: number) => {
            if (sourceId === targetId) {
                return;
            }
            const next = new Map(lists);
            const bucket = [...(next.get(parent) ?? [])];
            const from = bucket.findIndex((entry) => entry.id === sourceId);
            const to = bucket.findIndex((entry) => entry.id === targetId);
            if (from === -1 || to === -1) {
                return;
            }
            const [moved] = bucket.splice(from, 1);
            bucket.splice(to, 0, moved);
            next.set(parent, bucket);
            commit(next);
        },
        [lists, commit],
    );

    if (items.length === 0) {
        return <p className="text-small text-text-muted">{emptyText}</p>;
    }

    return (
        <ul className="flex flex-col gap-2" aria-label="Documentation order">
            {rows.map((row) => {
                const rowParent = row.depth === 0 ? 0 : row.parent;
                const isDragging = dragging?.parent === rowParent && dragging?.id === row.id;
                const isTarget = dragTarget?.parent === rowParent && dragTarget?.id === row.id;

                const handleDragStart = (event: DragEvent<HTMLLIElement>) => {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', String(row.id));
                    setDragging({ parent: rowParent, id: row.id });
                };

                const handleDragOver = (event: DragEvent<HTMLLIElement>) => {
                    if (!dragging || dragging.parent !== rowParent || dragging.id === row.id) {
                        return;
                    }
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    setDragTarget({ parent: rowParent, id: row.id });
                };

                const handleDrop = (event: DragEvent<HTMLLIElement>) => {
                    if (!dragging || dragging.parent !== rowParent) {
                        return;
                    }
                    event.preventDefault();
                    move(rowParent, dragging.id, row.id);
                    setDragging(null);
                    setDragTarget(null);
                };

                const handleDragEnd = () => {
                    setDragging(null);
                    setDragTarget(null);
                };

                return (
                    <li
                        key={row.id}
                        draggable
                        onDragStart={handleDragStart}
                        onDragOver={handleDragOver}
                        onDrop={handleDrop}
                        onDragEnd={handleDragEnd}
                        data-doc-id={row.id}
                        data-depth={row.depth}
                        style={{ marginLeft: row.depth * 32 }}
                        className={[
                            'flex items-center gap-3 rounded-box px-4 py-3 transition-colors cursor-move',
                            'bg-surface-2 text-text hover:bg-surface',
                            'border',
                            isTarget ? 'border-secondary' : 'border-border-subtle',
                            isDragging ? 'opacity-50' : '',
                        ]
                            .filter(Boolean)
                            .join(' ')}
                    >
                        <span aria-hidden="true" className="text-text-subtle">
                            =
                        </span>
                        <div className="min-w-0 flex-1">
                            <div className="truncate font-medium">{row.title}</div>
                            <div className="truncate text-xsmall text-text-muted">{row.slug}</div>
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}

export default DocsOrderer;
