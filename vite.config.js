import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import {globSync} from 'glob';
import tailwindcss from "@tailwindcss/vite";
import fs from 'fs';

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
                // All discovered assets from your modular structure
                ...moduleAssets
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});