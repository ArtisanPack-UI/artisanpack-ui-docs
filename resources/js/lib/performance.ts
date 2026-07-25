/**
 * Wires the artisanpack-ui/performance client helpers into the Inertia SPA.
 *
 * Two gates run before the Web Vitals collector boots:
 *
 *  1. Sensitive-path skip. Auth-flow URLs (password reset, email
 *     verification, two-factor challenge, privacy data-request verify)
 *     carry a secret token in the pathname. The shipped web-vitals
 *     module reads `window.location.pathname` and posts it as `page`,
 *     which the controller then persists to `performance_raw_metrics.url`
 *     for the full retention window — dropping live tokens straight
 *     into the RUM database. Skip the whole boot on those routes; the
 *     retention window has to see zero of these URLs.
 *
 *  2. Analytics consent. `config/artisanpack/privacy.php` categorises
 *     `_ga`-shaped tracking under `analytics`, and Web Vitals is the
 *     same shape of data (per-visitor page/URL/UA samples), so it
 *     rides the same category. Wait for
 *     `window.PrivacyConsent.whenConsented('analytics')` before
 *     importing the collector — data loss on decline is preferable to
 *     firing beacons the visitor didn't opt into.
 *
 * The speculative-rules helper only affects the browser's own prefetch
 * cache (no network beacon, no server storage) so it boots
 * unconditionally. Config is set synchronously; the collector is pulled
 * in with a dynamic import so its side-effect boot runs AFTER the
 * config global is populated and AFTER the consent wait resolves.
 *
 * Importing `@artisanpack-ui/privacy` here (as opposed to relying on it
 * being pulled in transitively by the layout's `<PrivacyBanners>`)
 * guarantees `window.PrivacyConsent` exists by the time this module
 * runs — the privacy main entry installs the global synchronously on
 * import and is idempotent under repeat imports.
 */
import '@artisanpack-ui/privacy';

import { csrfToken } from './csrf';

interface ConsentState {
    consents: Record<string, boolean>;
}

interface WindowPrivacyConsent {
    hasConsent: (category: string) => boolean;
    whenConsented: (category: string) => Promise<ConsentState>;
}

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
        PrivacyConsent?: WindowPrivacyConsent;
    }
}

const SENSITIVE_PATH_PATTERNS: RegExp[] = [
    /^\/reset-password(\/|$)/,
    /^\/forgot-password(\/|$)/,
    /^\/verify(\/|$)/,
    /^\/email\/verify(\/|$)/,
    /^\/two-factor-challenge(\/|$)/,
    /^\/user\/confirm-password(\/|$)/,
];

export function isSensitivePath(pathname: string): boolean {
    return SENSITIVE_PATH_PATTERNS.some((pattern) => pattern.test(pathname));
}

if (typeof window !== 'undefined' && !isSensitivePath(window.location.pathname)) {
    window.ArtisanPackPerformance = window.ArtisanPackPerformance ?? {};
    window.ArtisanPackPerformance.monitor = {
        endpoint: '/api/performance/metrics',
        sampleRate: 100,
        csrfToken: csrfToken() || null,
        page: window.location.pathname,
        route: null,
        extra: {},
    };

    void import('@artisanpack-ui/performance/speculative-rules');

    const analyticsConsent = window.PrivacyConsent?.whenConsented('analytics');

    if (analyticsConsent) {
        analyticsConsent
            .then(() => import('@artisanpack-ui/performance/web-vitals'))
            .catch(() => {
                // Consent was withdrawn, the wait was aborted, or the
                // privacy client failed to load. Silently skip the
                // collector — visibility into the RUM stream is not
                // worth firing beacons the visitor didn't opt into.
            });
    }
}
