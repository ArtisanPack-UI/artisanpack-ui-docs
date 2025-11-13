import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    build: {
        outDir: '../../public/build-auth',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-auth',
            hotFile: '../../public/hot-auth',
            input: [
                'Modules/Auth/resources/assets/css/auth.css',
                'Modules/Auth/resources/assets/js/auth.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
