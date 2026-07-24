<?php

declare(strict_types=1);

use Modules\Core\Services\IconResolverService;

it('returns null for empty input', function () {
    $service = new IconResolverService;

    expect($service->resolve(null))->toBeNull();
    expect($service->resolve(''))->toBeNull();
    expect($service->resolve('   '))->toBeNull();
});

it('maps fas.<name> to a Font Awesome solid class', function () {
    expect((new IconResolverService)->resolve('fas.house'))
        ->toBe(['type' => 'class', 'class' => 'fa-solid fa-house']);
});

it('maps fab.<name> to a Font Awesome brand class', function () {
    expect((new IconResolverService)->resolve('fab.github'))
        ->toBe(['type' => 'class', 'class' => 'fa-brands fa-github']);
});

it('maps far.<name> to a Font Awesome regular class', function () {
    expect((new IconResolverService)->resolve('far.calendar'))
        ->toBe(['type' => 'class', 'class' => 'fa-regular fa-calendar']);
});

it('passes through raw fa-* classes without prefixing them', function () {
    expect((new IconResolverService)->resolve('fa-solid fa-star'))
        ->toBe(['type' => 'class', 'class' => 'fa-solid fa-star']);
});

it('assumes fa-solid when a raw fa-* name has no explicit family', function () {
    expect((new IconResolverService)->resolve('fa-star'))
        ->toBe(['type' => 'class', 'class' => 'fa-solid fa-star']);
});

it('treats a bare name as a Font Awesome solid icon', function () {
    expect((new IconResolverService)->resolve('cube'))
        ->toBe(['type' => 'class', 'class' => 'fa-solid fa-cube']);
});

it('falls back to a Font Awesome class when a prefix has no configured icon set', function () {
    // `xx` isn't a registered set — service should treat as an FA family.
    expect((new IconResolverService)->resolve('xx.thing'))
        ->toBe(['type' => 'class', 'class' => 'fa-solid fa-thing']);
});

it('resolves ap.<name> against the configured icon set and returns inline SVG', function () {
    $result = (new IconResolverService)->resolve('ap.atom-simple');

    expect($result)->toMatchArray(['type' => 'svg']);
    expect($result['markup'])
        ->toContain('<svg')
        ->toContain('fill="currentColor"');
});

it('injects fill="currentColor" into SVGs that lack a root fill attribute', function () {
    config()->set('artisanpack.icons.sets', [
        'test' => [
            'prefix' => 'tst',
            'path' => sys_get_temp_dir().'/artisanpack-icon-tests',
        ],
    ]);

    $dir = sys_get_temp_dir().'/artisanpack-icon-tests';
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir.'/blank.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M0 0h10v10H0z"/></svg>');

    $result = (new IconResolverService)->resolve('tst.blank');

    expect($result)->toMatchArray(['type' => 'svg']);
    expect($result['markup'])->toContain('fill="currentColor"');

    unlink($dir.'/blank.svg');
});

it('strips HTML comments from resolved SVGs', function () {
    config()->set('artisanpack.icons.sets', [
        'test' => [
            'prefix' => 'tst',
            'path' => sys_get_temp_dir().'/artisanpack-icon-tests',
        ],
    ]);

    $dir = sys_get_temp_dir().'/artisanpack-icon-tests';
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents(
        $dir.'/commented.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><!-- license --><path d="M0 0"/></svg>'
    );

    $markup = (new IconResolverService)->resolve('tst.commented')['markup'];

    expect($markup)->not->toContain('<!--');
    expect($markup)->not->toContain('license');

    unlink($dir.'/commented.svg');
});

it('returns a class fallback when an ap.<name> file is missing on disk', function () {
    $result = (new IconResolverService)->resolve('ap.does-not-exist');

    expect($result)->toBe(['type' => 'class', 'class' => 'fa-solid fa-does-not-exist']);
});
