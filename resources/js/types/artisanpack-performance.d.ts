/**
 * Ambient module declarations for the `@artisanpack-ui/performance` subpath
 * exports we consume as pure side-effect imports. The upstream package
 * ships its own `.ts` types for the main entry, but the auxiliary
 * `/web-vitals` and `/speculative-rules` modules are plain `.js` files
 * whose side effect (boot the collector / install the fallback prefetcher)
 * is the only surface we use — no runtime type surface is needed.
 */
declare module '@artisanpack-ui/performance/web-vitals';
declare module '@artisanpack-ui/performance/speculative-rules';
