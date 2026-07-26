/**
 * Reads the CSRF token that Laravel writes into the layout's <meta> tag.
 * Returns '' when the DOM isn't available (SSR) or the tag is missing so
 * callers don't have to null-check every invocation.
 */
export function csrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}
