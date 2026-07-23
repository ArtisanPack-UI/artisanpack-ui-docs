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

    expect($source)->toContain("gridTemplateColumns: '290px 1fr 264px'");
});

it('gives DocsLayout a sticky top bar with grad-neon hairline and backdrop-blur', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/DocsLayout.tsx'));

    expect($source)
        ->toContain('sticky top-0')
        ->toContain('backdrop-blur-[14px]')
        ->toContain('rgba(8, 12, 22, 0.9)')
        ->toContain('var(--grad-neon)');
});

it('gives PublicLayout the same sticky top bar treatment', function () {
    $source = (string) file_get_contents(base_path('resources/js/layouts/PublicLayout.tsx'));

    expect($source)
        ->toContain('sticky top-0')
        ->toContain('backdrop-blur-[14px]')
        ->toContain('var(--grad-neon)');
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
