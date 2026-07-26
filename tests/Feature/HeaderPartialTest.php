<?php

declare(strict_types=1);

it('removes the placeholder search block now that #88 landed the React SearchOverlay', function () {
    $header = (string) file_get_contents(base_path('Modules/Core/resources/views/partials/header.blade.php'));

    expect($header)
        ->not->toContain('x-artisanpack-input')
        ->not->toContain('aria-hidden="true"')
        ->not->toContain('readonly')
        ->not->toContain('tabindex="-1"');
});

it('sets rel="noopener noreferrer" on every target="_blank" anchor in the header', function () {
    $header = (string) file_get_contents(base_path('Modules/Core/resources/views/partials/header.blade.php'));

    preg_match_all('/<a[^>]*target="_blank"[^>]*>/', $header, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $anchor) {
        expect($anchor)->toContain('rel="noopener noreferrer"');
    }
});

it('does not re-introduce Mary UI search-open handlers', function () {
    $header = (string) file_get_contents(base_path('Modules/Core/resources/views/partials/header.blade.php'));

    expect($header)
        ->not->toContain('mary-search-open')
        ->not->toContain('role="button"');
});
