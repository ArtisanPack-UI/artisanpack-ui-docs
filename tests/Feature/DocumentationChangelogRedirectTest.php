<?php

declare(strict_types=1);

it('301-redirects legacy /documentation/{package}/changelog(s) URLs to /changelogs/{package}', function (string $url, string $target) {
    $this->get($url)
        ->assertStatus(301)
        ->assertRedirect($target);
})->with([
    'singular, known slug' => ['/documentation/test-package/changelog',     '/changelogs/test-package'],
    'plural, known slug' => ['/documentation/test-package/changelogs',    '/changelogs/test-package'],
    'singular, unknown slug' => ['/documentation/unknown-package/changelog',  '/changelogs/unknown-package'],
    'plural, unknown slug' => ['/documentation/unknown-package/changelogs', '/changelogs/unknown-package'],
]);
