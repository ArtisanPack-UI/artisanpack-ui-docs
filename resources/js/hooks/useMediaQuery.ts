import { useEffect, useState } from 'react';

/**
 * Reactive `window.matchMedia` wrapper.
 *
 * SSR-safe: returns `defaultValue` before the first render on the
 * client, then switches to the live match. Use to render one branch of
 * a layout at a time (e.g. mobile-inline TOC vs. desktop-rail TOC)
 * instead of hiding one via CSS — the hidden copy would still mount
 * and attach its own IntersectionObservers.
 */
export function useMediaQuery(query: string, defaultValue = false): boolean {
    const [matches, setMatches] = useState<boolean>(defaultValue);

    useEffect(() => {
        if (typeof window === 'undefined' || !window.matchMedia) {
            return;
        }
        const list = window.matchMedia(query);
        const handleChange = (event: MediaQueryListEvent | MediaQueryList) => {
            setMatches(event.matches);
        };
        handleChange(list);
        list.addEventListener('change', handleChange);
        return () => list.removeEventListener('change', handleChange);
    }, [query]);

    return matches;
}
