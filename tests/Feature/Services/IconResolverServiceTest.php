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

function writeTestIcon(string $filename, string $markup): void
{
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

    file_put_contents($dir.'/'.$filename, $markup);
}

it('returns a null reference for empty input', function () {
    expect((new IconResolverService)->reference(null))->toBeNull();
    expect((new IconResolverService)->reference('  '))->toBeNull();
});

it('builds a reference with svg markup for a custom icon set', function () {
    $reference = (new IconResolverService)->reference('ap.puzzle');

    expect($reference)->toMatchArray(['raw' => 'ap.puzzle', 'set' => 'ap', 'name' => 'puzzle']);
    expect($reference['svg'])->toStartWith('<svg')->toContain('fill="currentColor"');
});

it('builds a reference without svg for Font Awesome and default-set icons', function (string $raw, string $set, string $name) {
    expect((new IconResolverService)->reference($raw))
        ->toBe(['raw' => $raw, 'set' => $set, 'name' => $name, 'svg' => null]);
})->with([
    'solid prefix' => ['fas.house', 'fas', 'house'],
    'brand prefix' => ['fab.github', 'fab', 'github'],
    'regular prefix' => ['far.calendar', 'far', 'calendar'],
    'unknown prefix falls back to solid' => ['xx.thing', 'fas', 'thing'],
    'bare name' => ['cube', 'fas', 'cube'],
    'raw class with family' => ['fa-brands fa-github', 'fab', 'github'],
    'raw class without family' => ['fa-star', 'fas', 'star'],
    'missing custom icon falls back to solid' => ['ap.does-not-exist', 'fas', 'does-not-exist'],
]);

it('strips script elements, event handlers, and external hrefs from custom SVGs', function () {
    writeTestIcon(
        'unsafe.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="alert(1)">'
        .'<script>alert(2)</script>'
        .'<foreignObject><div>x</div></foreignObject>'
        .'<set attributeName="href" to="javascript:alert(3)"/>'
        .'<a href="javascript:alert(4)"><path d="M0 0" onclick="alert(5)"/></a>'
        .'<use xlink:href="https://evil.test/sprite.svg#icon"/>'
        .'<use href="#local"/>'
        .'</svg>'
    );

    $markup = (new IconResolverService)->reference('tst.unsafe')['svg'];

    expect($markup)
        ->not->toContain('script')
        ->not->toContain('alert')
        ->not->toContain('foreignObject')
        ->not->toContain('<set')
        ->not->toContain('evil.test')
        ->not->toContain('onload')
        ->not->toContain('onclick')
        ->toContain('<path d="M0 0"')
        ->toContain('href="#local"');

    unlink(sys_get_temp_dir().'/artisanpack-icon-tests/unsafe.svg');
});

it('falls back to a class when a custom SVG is not well-formed', function () {
    writeTestIcon('broken.svg', '<svg><path></svg');

    expect((new IconResolverService)->resolve('tst.broken'))
        ->toBe(['type' => 'class', 'class' => 'fa-solid fa-broken']);

    unlink(sys_get_temp_dir().'/artisanpack-icon-tests/broken.svg');
});

it('rejects a custom file whose root element is not an svg', function () {
    writeTestIcon('html.svg', '<html><body onload="alert(1)"/></html>');

    expect((new IconResolverService)->reference('tst.html')['svg'])->toBeNull();

    unlink(sys_get_temp_dir().'/artisanpack-icon-tests/html.svg');
});

it('strips an external xlink:href that sits beside a local href on the same element', function () {
    writeTestIcon(
        'dual-href.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'
        .'<use href="#local" xlink:href="https://evil.test/sprite.svg#icon"/>'
        .'</svg>'
    );

    $markup = (new IconResolverService)->reference('tst.dual-href')['svg'];

    expect($markup)->not->toContain('evil.test')->toContain('href="#local"');

    unlink(sys_get_temp_dir().'/artisanpack-icon-tests/dual-href.svg');
});

it('strips style elements and attributes carrying script or external urls', function () {
    writeTestIcon(
        'styled.svg',
        '<svg xmlns="http://www.w3.org/2000/svg">'
        .'<style>@import url(https://evil.test/a.css);</style>'
        .'<path d="M0 0" style="fill: url(https://evil.test/b.svg#g)"/>'
        .'<path d="M1 1" filter="url(javascript:alert(1))"/>'
        .'<path d="M2 2" fill="url(#local-gradient)"/>'
        .'</svg>'
    );

    $markup = (new IconResolverService)->reference('tst.styled')['svg'];

    expect($markup)
        ->not->toContain('<style')
        ->not->toContain('evil.test')
        ->not->toContain('javascript')
        ->toContain('<path d="M0 0"')
        ->toContain('<path d="M1 1"');

    unlink(sys_get_temp_dir().'/artisanpack-icon-tests/styled.svg');
});
