import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';

import { AppShell } from './AppShell';

const appName = import.meta.env.VITE_APP_NAME || 'ArtisanPack UI Docs';

const rootPages = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx');
const modulePages = import.meta.glob<{ default: ComponentType }>(
    '../../Modules/*/resources/js/pages/**/*.tsx',
);

// Page name conventions:
//   'Welcome'      -> resources/js/pages/Welcome.tsx
//   'Admin::Dashboard' -> Modules/Admin/resources/js/pages/Dashboard.tsx
const resolvePage = (name: string) => {
    if (name.includes('::')) {
        const [module, page] = name.split('::');
        const key = `../../Modules/${module}/resources/js/pages/${page}.tsx`;
        return resolvePageComponent(key, modulePages);
    }
    return resolvePageComponent(`./pages/${name}.tsx`, rootPages);
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: resolvePage,
    setup({ el, App, props }) {
        const tree = (
            <AppShell>
                <App {...props} />
            </AppShell>
        );

        if (el.hasChildNodes()) {
            hydrateRoot(el, tree);
            return;
        }

        createRoot(el).render(tree);
    },
    progress: {
        // Picks up as the peg glow + spinner tint; the .bar background
        // itself is overridden to `var(--grad-neon)` in resources/css/app.css
        // so it matches the DocsLayout header hairline.
        color: '#00E5FF',
    },
}).catch((error: unknown) => {
    console.error('Inertia app failed to initialize', error);
});
