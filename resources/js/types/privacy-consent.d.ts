/**
 * Shared ambient shape for `window.PrivacyConsent` — the runtime API
 * installed by `@artisanpack-ui/privacy`'s side-effect entry point.
 * Kept here (instead of re-declared inside each `resources/js/lib/*`
 * consumer) so `analytics.ts` and `performance.ts` don't produce
 * TS2717 "subsequent property declarations must have the same type"
 * errors from duplicate Window augmentations.
 */

interface ArtisanPackPrivacyConsentState {
    regulation: string | null;
    consents: Record<string, boolean>;
    categories: Record<string, { name?: string; description?: string; required?: boolean }>;
}

interface WindowPrivacyConsent {
    hasConsent: (category: string) => boolean;
    load: () => Promise<ArtisanPackPrivacyConsentState>;
    whenConsented: (category: string) => Promise<ArtisanPackPrivacyConsentState>;
}

declare global {
    interface Window {
        PrivacyConsent?: WindowPrivacyConsent;
    }
}

export {};
