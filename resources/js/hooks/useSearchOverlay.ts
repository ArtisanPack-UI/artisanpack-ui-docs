import { useCallback, useEffect, useState } from 'react';

export interface UseSearchOverlayReturn {
    open: boolean;
    openOverlay: () => void;
    closeOverlay: () => void;
    toggleOverlay: () => void;
}

/**
 * Owns the global ⌘K / Ctrl+K listener that drives the SearchOverlay's
 * open state.
 *
 * The listener treats ⌘K on macOS and Ctrl+K everywhere else as the
 * canonical trigger and swallows the default browser shortcut (Chrome's
 * omnibox search prefix, Firefox's search bar focus) so it can't fire
 * underneath the overlay.
 */
export function useSearchOverlay(): UseSearchOverlayReturn {
    const [open, setOpen] = useState(false);

    const openOverlay = useCallback(() => setOpen(true), []);
    const closeOverlay = useCallback(() => setOpen(false), []);
    const toggleOverlay = useCallback(() => setOpen((current) => !current), []);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'k' && event.key !== 'K') {
                return;
            }
            if (!(event.metaKey || event.ctrlKey) || event.altKey) {
                return;
            }
            event.preventDefault();
            setOpen((current) => !current);
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    return { open, openOverlay, closeOverlay, toggleOverlay };
}

export default useSearchOverlay;
