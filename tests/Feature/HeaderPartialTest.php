<?php

declare(strict_types=1);

it('marks the pending search block as aria-hidden until #88 lands the React SearchOverlay', function () {
    $header = (string) file_get_contents(base_path('Modules/Core/resources/views/partials/header.blade.php'));

    expect($header)->toContain('aria-hidden="true"');
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
