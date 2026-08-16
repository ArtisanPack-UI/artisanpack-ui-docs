/**
 * Wires the artisanpack-ui/analytics client tracker into the Inertia SPA.
 *
 * Same two-gate pattern as `./performance.ts`:
 *
 *  1. Sensitive-path skip. Auth-flow URLs (password reset, email
 *     verification, two-factor challenge, privacy data-request verify)
 *     carry a secret token in the pathname. The tracker reads
 *     `window.location.pathname` and persists it on `analytics_page_views.url`
 *     for the retention window — dropping live tokens straight into the
 *     analytics database. Skip the whole boot on those routes; the
 *     retention window has to see zero of these URLs.
 *
 *     This boot-time gate only sees the entry URL. SPA navigation *into* a
 *     sensitive path from a normal page is covered separately: the vendor
 *     tracker's own History-API tracking is disabled
 *     (`trackHistoryChanges: false`) because it has no client-side path
 *     exclusion, and the guarded `inertia:navigate` bridge installed after
 *     consent re-checks `isSensitivePath` on every navigation. So these
 *     URLs never leave the browser, and `privacy.excluded_paths` remains
 *     the server-side belt for anything that does reach `/api/analytics/*`.
 *
 *  2. Analytics consent. Both this package and the perf collector share the
 *     `analytics` consent category from `config/artisanpack/privacy.php`,
 *     so nothing loads until `window.PrivacyConsent.whenConsented('analytics')`
 *     resolves. Data loss on decline is preferable to firing beacons the
 *     visitor didn't opt into.
 *
 * The tracker is loaded via dynamic ESM import of the vendor IIFE. The
 * IIFE installs `window.ArtisanPackAnalytics` and auto-inits on DOM
 * ready, so any custom event we want to fire has to wait for
 * `_initialized`. `trackDocsEvent` handles that queue so callers can
 * fire events immediately at mount without race conditions.
 */
import '@artisanpack-ui/privacy';

// `window.PrivacyConsent` is declared once in
// `../types/privacy-consent.d.ts` so both this file and
// `./performance.ts` share the same interface (TS2717 would fire
// otherwise). Keep the analytics-tracker globals scoped here.
declare global {
    interface Window {
        __ARTISANPACK_ANALYTICS_CONFIG__?: Record<string, unknown>;
        ArtisanPackAnalytics?: {
            _initialized?: boolean;
            event?: (
                name: string,
                properties?: Record<string, unknown>,
                options?: { category?: string; value?: number },
            ) => void;
            pageView?: (customData?: Record<string, unknown>) => void;
            consent?: {
                grant: (categories?: string | string[]) => void;
                revoke: (categories?: string | string[]) => void;
                hasConsent: (category: string) => boolean;
            };
        };
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

/**
 * Reads the `<meta name="analytics-track">` flag rendered by the
 * server. Blade emits `"false"` for any authenticated request so admin
 * activity doesn't skew visitor stats; anything else (missing tag,
 * empty content, `"true"`) opts in.
 */
/**
 * Debug flag consulted by the tracker boot chain's error branch.
 * Enable per-visit with `?analytics-debug=1`, or across visits by
 * setting `localStorage.setItem('analytics-debug','1')`. The check
 * defaults to `false` so production users never see console noise.
 */
function isAnalyticsDebugEnabled(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }
    try {
        if (new URLSearchParams(window.location.search).get('analytics-debug') === '1') {
            return true;
        }
    } catch {
        // URLSearchParams is available everywhere the tracker runs,
        // but guard anyway so a hardened iframe can't crash the boot.
    }
    try {
        return window.localStorage.getItem('analytics-debug') === '1';
    } catch {
        return false;
    }
}

export function shouldTrackAnalytics(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }
    const meta = document.querySelector<HTMLMetaElement>('meta[name="analytics-track"]');
    // Default to true when the tag is absent so removing it in a
    // partial migration errs on the side of collecting data (visitor
    // pages, guest sessions). There is no server-side belt: the
    // vendor `/api/analytics/*` routes ride the `api` middleware
    // group which has no `StartSession`, so no server-side auth
    // check can distinguish authed vs guest beacons — this meta
    // gate is the whole story.
    if (!meta) {
        return true;
    }
    return meta.content.trim().toLowerCase() !== 'false';
}

const EVENT_QUEUE: Array<{
    name: string;
    properties: Record<string, unknown>;
    category?: string;
    value?: number;
}> = [];

let trackerReady = false;

function flushQueue(): void {
    if (!window.ArtisanPackAnalytics?.event) {
        return;
    }
    trackerReady = true;
    while (EVENT_QUEUE.length > 0) {
        const entry = EVENT_QUEUE.shift();
        if (!entry) {
            continue;
        }
        window.ArtisanPackAnalytics.event(
            entry.name,
            entry.properties,
            entry.category !== undefined || entry.value !== undefined
                ? { category: entry.category, value: entry.value }
                : undefined,
        );
    }
}

/**
 * Track a custom docs event. Safe to call before the tracker is loaded
 * or before analytics consent is granted — events queue and flush the
 * moment the tracker becomes available. Silently no-ops on sensitive
 * paths or when consent has been declined.
 */
export function trackDocsEvent(
    name: string,
    properties: Record<string, unknown> = {},
    options: { category?: string; value?: number } = {},
): void {
    if (typeof window === 'undefined') {
        return;
    }
    if (!shouldTrackAnalytics()) {
        return;
    }
    if (isSensitivePath(window.location.pathname)) {
        return;
    }
    if (trackerReady && window.ArtisanPackAnalytics?.event) {
        window.ArtisanPackAnalytics.event(name, properties, options);
        return;
    }
    EVENT_QUEUE.push({ name, properties, ...options });
}

if (
    typeof window !== 'undefined' &&
    !isSensitivePath(window.location.pathname) &&
    shouldTrackAnalytics()
) {
    window.__ARTISANPACK_ANALYTICS_CONFIG__ = {
        endpoint: '/api/analytics',
        // The privacy package is the single source of truth for consent —
        // the tracker's built-in `consentRequired` gate would independently
        // set its own cookie and race the privacy banner. Skip the loader
        // entirely until privacy resolves, and let the tracker run
        // unconstrained once we do load it.
        consentRequired: false,
        consentCategory: 'analytics',
        respectDNT: true,
        trackPageViews: true,
        trackHashChanges: false,
        // Own SPA navigation tracking here rather than let the tracker's
        // History-API wrapper (default on in 1.5) do it. The vendor wrapper
        // has no client-side path exclusion, so it would POST sensitive
        // auth URLs (password-reset / verify tokens) to `/api/analytics/*`
        // where they can land in web-server logs — the exact leak gate #1
        // exists to prevent. Turning it off and re-installing the guarded
        // `inertia:navigate` bridge below keeps those URLs in the browser
        // and, because there is then exactly one navigation tracker, avoids
        // the double-counting that removing the bridge originally fixed.
        trackHistoryChanges: false,
        trackOutboundLinks: true,
        trackFileDownloads: true,
        debug: false,
    };

    const client = window.PrivacyConsent;

    if (client) {
        // The privacy client's `load()` intentionally calls setState with
        // `dispatch: false`, so `onChange` subscribers do NOT fire on
        // initial hydration. `whenConsented()` only checks the sync
        // `hasConsent()` fast-path once at call time — before load
        // completes it returns false, subscribes to onChange, and then
        // sits pending forever for any visitor whose consent already
        // includes 'analytics'. Trigger `load()` explicitly first so the
        // fast-path fires on the next tick, then defer to `whenConsented`
        // (which still handles the fresh-visitor grant path via the
        // dispatch that DOES fire out of `setConsent`/`setConsents`).
        client
            .load()
            .catch(() => {
                // Network failure or 4xx from /api/privacy/consent. Do
                // not block the consent gate on transport errors — fall
                // through to `whenConsented` which will still resolve
                // if the visitor grants consent via the UI.
            })
            .then(() => client.whenConsented('analytics'))
            .then(() => import('@artisanpack-ui/analytics/tracker'))
            .then(() => {
                // The tracker IIFE auto-inits on DOMContentLoaded; when the
                // dynamic import lands post-load it inits synchronously.
                // Either way, ArtisanPackAnalytics._initialized flips
                // before this .then fires the next tick, so flushing the
                // queue here is safe.
                flushQueue();

                // Bridge Inertia's `inertia:navigate` DOM event into a
                // manual pageView call so each SPA route change lands on
                // `analytics_page_views`. The tracker's built-in History-API
                // tracking is disabled (`trackHistoryChanges: false` above),
                // so this bridge is the sole navigation tracker — no double
                // counting — and it re-checks `isSensitivePath` on every
                // navigation, keeping token-bearing auth URLs in the browser
                // exactly as the boot-time gate does for the entry URL.
                //
                // The event name matters: `@inertiajs/core` dispatches
                // `inertia:navigate` (see `fireNavigateEvent` in the core
                // bundle), NOT `inertia:navigated`. A `-d` at the end
                // silently no-ops.
                const onNavigate = (): void => {
                    if (isSensitivePath(window.location.pathname)) {
                        return;
                    }
                    window.ArtisanPackAnalytics?.pageView?.();
                };
                document.addEventListener('inertia:navigate', onNavigate);
            })
            .catch((error: unknown) => {
                // Consent was withdrawn, the wait was aborted, or the
                // tracker chunk failed to load. Silently drop queued
                // events — they were opt-in data the visitor did not
                // authorise. Surface the underlying error when
                // `?analytics-debug=1` (or a persisted equivalent) is
                // on, so a future vendor bump that breaks init doesn't
                // hide behind an empty catch.
                EVENT_QUEUE.length = 0;
                if (isAnalyticsDebugEnabled()) {
                    // eslint-disable-next-line no-console
                    console.warn('[analytics] tracker boot aborted', error);
                }
            });
    }
}
