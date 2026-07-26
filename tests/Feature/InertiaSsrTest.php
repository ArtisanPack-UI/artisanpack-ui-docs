<?php

declare(strict_types=1);

it('has SSR enabled in Inertia config', function () {
    expect(config('inertia.ssr.enabled'))->toBeTrue();
});

it('points Inertia SSR at a local Node bundle port', function () {
    $url = config('inertia.ssr.url');

    expect($url)
        ->toBeString()
        ->toStartWith('http://127.0.0.1:');
});

it('ships an SSR entry point at resources/js/ssr.tsx', function () {
    $entry = base_path('resources/js/ssr.tsx');

    expect(file_exists($entry))->toBeTrue();

    $contents = (string) file_get_contents($entry);

    expect($contents)
        ->toContain("from '@inertiajs/react/server'")
        ->toContain('ReactDOMServer.renderToString')
        ->toContain('import.meta.glob')
        ->toContain('./pages/**/*.tsx')
        ->toContain('../../Modules/*/resources/js/pages/**/*.tsx');
});

it('registers the SSR entry with Vite', function () {
    $config = (string) file_get_contents(base_path('vite.config.js'));

    expect($config)->toContain("ssr: 'resources/js/ssr.tsx'");
});

it('builds the SSR bundle as part of npm run build', function () {
    $package = json_decode((string) file_get_contents(base_path('package.json')), true);

    expect($package['scripts']['build'] ?? '')
        ->toContain('vite build')
        ->toContain('--ssr');
});
