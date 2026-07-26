<?php

declare(strict_types=1);

it('ships all four shared Inertia layouts', function () {
    foreach (['PublicLayout', 'DocsLayout', 'AdminLayout', 'AuthLayout'] as $layout) {
        expect(file_exists(base_path("resources/js/layouts/{$layout}.tsx")))
            ->toBeTrue("Missing layout: {$layout}");
    }
});

it('renders DocsLayout with the 3-column precision-rail grid', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/DocsLayout.tsx'));

    expect($source)->toContain('xl:grid-cols-[290px_1fr_264px]');
});

it('gives DocsLayout a theme-aware sticky top bar with grad-neon hairline and backdrop-blur', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/DocsLayout.tsx'));

    expect($source)
        ->toContain('sticky top-0')
        ->toContain('backdrop-blur-[14px]')
        ->toContain('bg-base/90')
        ->toContain('var(--grad-neon)');
});

it('measures the DocsLayout header height so sticky rails track it', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/DocsLayout.tsx'));

    expect($source)
        ->toContain('--docs-header-h')
        ->toContain('ResizeObserver');
});

it('gives PublicLayout the same theme-aware sticky top bar treatment', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/PublicLayout.tsx'));

    expect($source)
        ->toContain('sticky top-0')
        ->toContain('backdrop-blur-[14px]')
        ->toContain('bg-base/90')
        ->toContain('var(--grad-neon)');
});

it('collapses DocsLayout to a single column below xl to avoid overflow on narrow viewports', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/DocsLayout.tsx'));

    expect($source)
        ->toContain('grid-cols-1')
        ->toContain('xl:grid-cols-[290px_1fr_264px]');
});

it('picks a single active AdminLayout nav item by longest-prefix match', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/AdminLayout.tsx'));

    expect($source)
        ->toContain('activeHref')
        ->toContain('(b.matchPrefix ?? b.href).length - (a.matchPrefix ?? a.href).length');
});

it('does not emit an empty AdminLayout heading when title is omitted', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/AdminLayout.tsx'));

    expect($source)->toContain('title ? <h1');
});

it('gives AdminLayout a sidebar shell', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/AdminLayout.tsx'));

    expect($source)
        ->toContain("gridTemplateColumns: '260px 1fr'")
        ->toContain('Admin navigation');
});

it('gives AuthLayout an aurora background with a centered card', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/AuthLayout.tsx'));

    expect($source)
        ->toContain('ap-aurora')
        ->toContain('items-center justify-center')
        ->toContain('Card');
});
