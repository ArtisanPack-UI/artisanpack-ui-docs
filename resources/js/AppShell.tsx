import { ThemeProvider, useTheme, type ColorScheme } from '@artisanpack-ui/react';
import { useEffect, type ReactNode } from 'react';

const isBrowser = typeof document !== 'undefined';

function readInitialColorScheme(): ColorScheme {
    if (!isBrowser) {
        return 'light';
    }
    const attr = document.documentElement.getAttribute('data-theme');
    return attr === 'dark' ? 'dark' : 'light';
}

function ThemeDomSync({ children }: { children: ReactNode }) {
    const { resolvedColorScheme } = useTheme();

    useEffect(() => {
        const root = document.documentElement;
        root.setAttribute('data-theme', resolvedColorScheme);
        root.classList.toggle('dark', resolvedColorScheme === 'dark');
        try {
            localStorage.setItem('theme', resolvedColorScheme);
        } catch {
            // localStorage disabled (private mode / disk full) — the DOM sync is enough.
        }
    }, [resolvedColorScheme]);

    return <>{children}</>;
}

export function AppShell({ children }: { children: ReactNode }) {
    return (
        <ThemeProvider defaultColorScheme={readInitialColorScheme()}>
            <ThemeDomSync>{children}</ThemeDomSync>
        </ThemeProvider>
    );
}
