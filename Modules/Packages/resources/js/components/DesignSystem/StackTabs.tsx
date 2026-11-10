import { useRef, type KeyboardEvent } from 'react';

import {
    STACK_LABELS,
    stackPanelId,
    stackTabId,
    type ComponentStack,
    type StackKey,
} from './types';

export interface StackTabsProps {
    stacks: ComponentStack[];
    active: StackKey;
    onChange: (stack: StackKey) => void;
    /** Prefix for the tab and panel ids. */
    idPrefix: string;
}

/**
 * Blade / React / Vue tabs following the WAI-ARIA tabs pattern: one tab
 * stop (roving tabindex), Left/Right arrows move and activate, Home/End
 * jump to the first/last tab.
 */
export function StackTabs({ stacks, active, onChange, idPrefix }: StackTabsProps) {
    const tabRefs = useRef<Partial<Record<StackKey, HTMLButtonElement | null>>>({});

    const focusAndSelect = (index: number) => {
        const stack = stacks[(index + stacks.length) % stacks.length];
        if (!stack) {
            return;
        }
        onChange(stack.key);
        tabRefs.current[stack.key]?.focus();
    };

    const onKeyDown = (event: KeyboardEvent<HTMLButtonElement>, index: number) => {
        switch (event.key) {
            case 'ArrowRight':
                focusAndSelect(index + 1);
                break;
            case 'ArrowLeft':
                focusAndSelect(index - 1);
                break;
            case 'Home':
                focusAndSelect(0);
                break;
            case 'End':
                focusAndSelect(stacks.length - 1);
                break;
            default:
                return;
        }
        event.preventDefault();
    };

    return (
        <div
            role="tablist"
            aria-label="Framework"
            className="inline-flex gap-1 rounded-[10px] border border-border-subtle bg-surface-2 p-1"
        >
            {stacks.map((stack, index) => {
                const selected = stack.key === active;
                return (
                    <button
                        key={stack.key}
                        ref={(el) => {
                            tabRefs.current[stack.key] = el;
                        }}
                        type="button"
                        role="tab"
                        id={stackTabId(idPrefix, stack.key)}
                        aria-selected={selected}
                        aria-controls={selected ? stackPanelId(idPrefix, stack.key) : undefined}
                        tabIndex={selected ? 0 : -1}
                        onClick={() => onChange(stack.key)}
                        onKeyDown={(event) => onKeyDown(event, index)}
                        className={`rounded-[7px] px-4 py-1.5 text-[14px] font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary ${
                            selected
                                ? 'bg-surface text-text shadow-sm'
                                : 'text-text-muted hover:text-text'
                        }`}
                    >
                        {STACK_LABELS[stack.key]}
                    </button>
                );
            })}
        </div>
    );
}
