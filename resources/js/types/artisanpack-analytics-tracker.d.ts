/**
 * Ambient module declaration for the vanilla-JS analytics helper shipped
 * by `artisanpack-ui/analytics`. The compiled bundle installs
 * `window.APAnalytics` on import; this shim types only the small surface
 * we call from the app (custom events, page-view refresh) plus the two
 * globals the tracker publishes.
 */

export interface APAnalyticsEventOptions {
    category?: string;
    value?: number;
    sourcePackage?: string;
}

export interface APAnalyticsInstance {
    version: string;
    configure: (options: Record<string, unknown>) => APAnalyticsInstance;
    identify?: (id: string, traits?: Record<string, unknown>) => APAnalyticsInstance;
    track: (
        name: string,
        properties?: Record<string, unknown>,
        options?: APAnalyticsEventOptions,
    ) => APAnalyticsInstance;
    trackPageView?: (url?: string, title?: string) => APAnalyticsInstance;
    enable?: () => APAnalyticsInstance;
    disable?: () => APAnalyticsInstance;
}

// The `window.ArtisanPackAnalytics` shape is declared where it is
// consumed (`resources/js/lib/analytics.ts`) to keep a single source
// of truth for the tracker's runtime surface — duplicating the
// declaration here fights TS's declaration-merging rules.
declare global {
    interface Window {
        APAnalytics?: APAnalyticsInstance;
    }
}

declare const APAnalytics: APAnalyticsInstance;
export default APAnalytics;
