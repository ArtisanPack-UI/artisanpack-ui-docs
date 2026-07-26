/**
 * The vendor `InertiaDashboardController` passes each analytics prop
 * through a Laravel API Resource before handing it to Inertia. Laravel
 * wraps `JsonResource::toArray()` output in `{ data: … }` before serializing,
 * but the vendor React components expect the unwrapped shape (`stats.bounce_rate`,
 * not `stats.data.bounce_rate`). Rather than patch the vendor controller
 * or globally call `JsonResource::withoutWrapping()` (which would leak to
 * every other API Resource in the app), each thin page wrapper runs its
 * props through `unwrap()` so the vendor components see the shape they
 * were built for.
 */

/**
 * If `value` looks like `{ data: T }`, return the inner `data`. Otherwise
 * return `value` as-is. Also tolerates `null`/`undefined` by returning a
 * caller-provided fallback so the vendor components never receive
 * `undefined` for a prop they treat as required.
 */
export function unwrap<T>(value: T | { data: T } | null | undefined, fallback: T): T {
    if (value === null || value === undefined) {
        return fallback;
    }
    if (
        typeof value === 'object' &&
        value !== null &&
        'data' in value &&
        Object.keys(value as Record<string, unknown>).length === 1
    ) {
        return (value as { data: T }).data;
    }
    return value as T;
}
