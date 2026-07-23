import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { globSync } from 'glob';
import tailwindcss from '@tailwindcss/vite';

const moduleAssets = globSync(
    'Modules/*/resources/assets/{js,css,scss}/*.{js,scss,css}',
);

export default defineConfig({
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
        },
    },
});
