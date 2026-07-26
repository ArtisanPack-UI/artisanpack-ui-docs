import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { globSync } from 'glob';
import tailwindcss from '@tailwindcss/vite';

const moduleAssets = globSync(
    'Modules/*/resources/assets/{js,css,scss}/*.{js,scss,css}',
);

export default defineConfig(({ isSsrBuild }) => ({
    // In dev, Vite serves resources/css/app.css from its own origin
    // (https://<host>:5173), so `url('/fonts/…')` inside that CSS resolves
    // against Vite — not Laravel. Point publicDir at Laravel's public/ so
    // Vite's built-in static handler serves those assets in dev; disable
    // copyPublicDir so the build doesn't duplicate them into public/build/.
    publicDir: 'public',
    build: {
        copyPublicDir: false,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
                ...moduleAssets,
            ],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
            detectTls: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            // Stub `apexcharts` and `react-apexcharts` in the SSR bundle
            // only. Both touch `window` / `document` / `getComputedStyle`
            // at module load and crash Node the moment ssr.js starts —
            // and `@artisanpack-ui/react`'s barrel pulls them in
            // transitively via its Chart chunk, so even importing
            // `ThemeToggle` from the barrel is enough to trip it. The
            // stub renders `null`; on the client the real packages
            // resolve as normal.
            ...(isSsrBuild && {
                'react-apexcharts': fileURLToPath(
                    new URL(
                        './resources/js/stubs/ApexChartsSsrStub.tsx',
                        import.meta.url,
                    ),
                ),
                apexcharts: fileURLToPath(
                    new URL(
                        './resources/js/stubs/ApexChartsSsrStub.tsx',
                        import.meta.url,
                    ),
                ),
            }),
            // The vendor ships both `tracker.js` (the core IIFE that
            // installs `window.ArtisanPackAnalytics` and auto-inits on
            // DOMContentLoaded) and `analytics-api.js` (a thin
            // `window.APAnalytics` wrapper that no-ops until the core
            // tracker is present). Alias at the core — the wrapper is
            // useless on its own. The composer package ships this JS
            // without a top-level npm package.json, so `file:`-linking
            // the way privacy and performance are wired is not an
            // option; the alias is the intended integration path.
            //
            // Same reasoning for the google and analytics-google React
            // components used by the Integrations page — both ship
            // JS-only package.jsons marked `private:true` (npm refuses
            // to publish), so their `resources/js/react/index.ts`
            // entries are aliased here too.
            '@artisanpack-ui/analytics/tracker': fileURLToPath(
                new URL(
                    './vendor/artisanpack-ui/analytics/resources/js/tracker.js',
                    import.meta.url,
                ),
            ),
            '@artisanpack-ui/analytics-google/react': fileURLToPath(
                new URL(
                    './vendor/artisanpack-ui/analytics-google/resources/js/react/index.ts',
                    import.meta.url,
                ),
            ),
            '@artisanpack-ui/google/react': fileURLToPath(
                new URL(
                    './vendor/artisanpack-ui/google/resources/js/react/index.ts',
                    import.meta.url,
                ),
            ),
        },
    },
    // Force Vite to bundle these into the SSR output rather than
    // externalizing them (Node's require bypasses `resolve.alias`).
    // `@artisanpack-ui/react` has to be here too — Vite would otherwise
    // externalize it, letting Node load its barrel and transitively
    // pull `react-apexcharts` before our alias ever gets a chance.
    // Bundling forces every import through Vite's resolver so the
    // SSR stub above wins.
    ssr: {
        noExternal: [/^@artisanpack-ui\/react($|\/)/, 'react-apexcharts', 'apexcharts'],
    },
}));
