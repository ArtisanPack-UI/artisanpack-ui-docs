/**
 * Per-page prop contracts.
 *
 * Every Inertia controller must declare its page prop shape here and
 * consume it from its page component:
 *
 * ```ts
 * // resources/js/types/pages.ts
 * export type PackageShowPageProps = PageProps<{
 *     package: { name: string; slug: string };
 * }>;
 *
 * // resources/js/pages/Packages/Show.tsx
 * export default function Show({ package: pkg }: PackageShowPageProps) { ... }
 * ```
 *
 * `PageProps<T>` layers T on top of the app-wide `SharedProps` contract
 * declared in `inertia.d.ts`, so shared keys (like `flash`) stay
 * available on every page without repeating them.
 */
export type { PageProps, SharedProps, FlashData } from './inertia';
