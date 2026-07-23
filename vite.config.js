import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { globSync } from 'glob';
import tailwindcss from '@tailwindcss/vite';

// Discover assets from your core Modules
const moduleAssets = globSync('Modules/*/resources/assets/{js,css,scss}/*.{js,scss,css}');

// Discover assets from your future Plugins
const pluginAssets = globSync('plugins/*/assets/{js,css,scss}/*.{js,scss,css}');

// Discover assets from your future Themes
const themeAssets = globSync('themes/*/assets/{js,css,scss}/*.{js,scss,css}');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.tsx',
                // All discovered assets from your modular structure
                ...moduleAssets,
            ],
            refresh: true,
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
