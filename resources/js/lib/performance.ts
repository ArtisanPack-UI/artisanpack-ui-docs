/**
 * Wires the artisanpack-ui/performance client helpers into the Inertia SPA.
 *
 * The `@artisanpack-ui/performance/web-vitals` module reads its endpoint,
 * sample rate, and CSRF token off `window.ArtisanPackPerformance.monitor`
 * at boot. The Blade `@perfMonitor` directive normally writes that global
 * inline, but the shipped module `import`s the `web-vitals` package as a
 * bare specifier which the browser cannot resolve on its own — so we bundle
 * it through Vite instead and set the global here.
 *
 * Config is set synchronously; the modules are pulled in with dynamic
 * imports so their side-effect boot code runs AFTER the config global is
 * populated (static imports would hoist and boot the collector against an
 * empty config).
 */
import { csrfToken } from './csrf';

declare global {
    interface Window {
        ArtisanPackPerformance?: {
            monitor?: {
                endpoint: string;
                sampleRate: number;
                csrfToken: string | null;
                page: string | null;
                route: string | null;
                extra: Record<string, unknown>;
            };
            webVitals?: unknown;
        };
    }
}

if (typeof window !== 'undefined') {
    window.ArtisanPackPerformance = window.ArtisanPackPerformance ?? {};
    window.ArtisanPackPerformance.monitor = {
        endpoint: '/api/performance/metrics',
        sampleRate: 100,
        csrfToken: csrfToken() || null,
        page: window.location.pathname,
        route: null,
        extra: {},
    };

    void import('@artisanpack-ui/performance/web-vitals');
    void import('@artisanpack-ui/performance/speculative-rules');
}
