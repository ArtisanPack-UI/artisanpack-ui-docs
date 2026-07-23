import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import type { ComponentType } from 'react';
import ReactDOMServer from 'react-dom/server';

const appName = import.meta.env.VITE_APP_NAME || 'ArtisanPack UI Docs';

const rootPages = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx', { eager: true });
const modulePages = import.meta.glob<{ default: ComponentType }>(
    '../../Modules/*/resources/js/pages/**/*.tsx',
    { eager: true },
);

const resolvePage = (name: string) => {
    if (name.includes('::')) {
        const [module, page] = name.split('::');
        const key = `../../Modules/${module}/resources/js/pages/${page}.tsx`;
        const found = modulePages[key];
        if (!found) {
            throw new Error(`Inertia SSR page not found: ${name} (${key})`);
        }
        return found.default;
    }

    const key = `./pages/${name}.tsx`;
    const found = rootPages[key];
    if (!found) {
        throw new Error(`Inertia SSR page not found: ${name} (${key})`);
    }
    return found.default;
};

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: resolvePage,
        setup: ({ App, props }) => <App {...props} />,
    }),
);
