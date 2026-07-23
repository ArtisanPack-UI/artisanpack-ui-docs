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
//   'Auth::Login'  -> Modules/Auth/resources/js/pages/Login.tsx
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
        color: '#4B5563',
    },
}).catch((error: unknown) => {
    console.error('Inertia app failed to initialize', error);
});
