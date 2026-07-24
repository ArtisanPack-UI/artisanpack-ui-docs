<?php

declare(strict_types=1);

it('ships a SearchOverlay React component with the ⌘K plumbing', function () {
    $overlay = (string) file_get_contents(base_path('resources/js/components/SearchOverlay.tsx'));

    expect($overlay)
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('role="listbox"')
        ->toContain('role="option"')
        ->toContain('router.visit')
        ->toContain("'ArrowDown'")
        ->toContain("'ArrowUp'")
        ->toContain("'Enter'")
        ->toContain("'Escape'");
});

it('ships a useSearchOverlay hook that owns the ⌘K global listener', function () {
    $hook = (string) file_get_contents(base_path('resources/js/hooks/useSearchOverlay.ts'));

    expect($hook)
        ->toContain("addEventListener('keydown'")
        ->toContain('event.metaKey || event.ctrlKey')
        ->toContain('preventDefault');
});

it('mounts the SearchOverlay inside DocsLayout and wires the header button to it', function () {
    $layout = (string) file_get_contents(base_path('resources/js/layouts/DocsLayout.tsx'));

    expect($layout)
        ->toContain("from '../components/SearchOverlay'")
        ->toContain("from '../hooks/useSearchOverlay'")
        ->toContain('useSearchOverlay()')
        ->toContain('<SearchOverlay')
        ->toContain('onClick={openSearch}')
        ->toContain('aria-label="Open search (⌘K)"');
});

it('registers the /search route pointing at SearchController@search', function () {
    $routes = (string) file_get_contents(base_path('Modules/Core/routes/web.php'));

    expect($routes)
        ->toContain('SearchController')
        ->toContain("Route::get('/search'")
        ->toContain("->name('search')");
});
