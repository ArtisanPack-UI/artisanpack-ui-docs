<?php

declare(strict_types=1);

use App\Http\HandleInertiaRequests;
use Illuminate\Foundation\Http\Kernel;
use Inertia\Inertia;

it('registers HandleInertiaRequests on the web middleware group', function () {
    $middleware = app(Kernel::class)->getMiddlewareGroups()['web'] ?? [];

    expect($middleware)->toContain(HandleInertiaRequests::class);
});

it('renders the root Blade template as valid HTML for an Inertia page', function () {
    $html = Inertia::render('Welcome')
        ->toResponse(request())
        ->getContent();

    expect($html)
        ->toContain('<div id="app"')
        ->toContain('data-page=')
        ->toContain('type="module"');
});

it('boots the theme on the Inertia root before Vite loads to avoid FOUC', function () {
    $html = Inertia::render('Welcome')
        ->toResponse(request())
        ->getContent();

    // The Blade @vite directive renders differently in dev (public/hot present, dev URLs) vs
    // prod (build/manifest.json present, hashed asset URLs). Both modes emit type="module" on
    // the app entry script, so use that as the manifest-agnostic anchor for Vite's output.
    $bootPos = strpos($html, "root.setAttribute('data-theme', theme)");
    $viteModulePos = strpos($html, 'type="module"');
    $headClosePos = strpos($html, '</head>');

    expect($bootPos)->not->toBeFalse();
    expect($viteModulePos)->not->toBeFalse();
    expect($headClosePos)->not->toBeFalse();
    expect($bootPos)->toBeLessThan($viteModulePos);
    expect($bootPos)->toBeLessThan($headClosePos);
});

it('wraps the Inertia app in the shared AppShell (theme provider + DOM sync)', function () {
    $entry = (string) file_get_contents(base_path('resources/js/app.tsx'));
    $ssr = (string) file_get_contents(base_path('resources/js/ssr.tsx'));

    expect($entry)
        ->toContain("import { AppShell } from './AppShell'")
        ->toContain('<AppShell>');
    expect($ssr)
        ->toContain("import { AppShell } from './AppShell'")
        ->toContain('<AppShell>');
});

it('syncs ThemeProvider color scheme onto the document root', function () {
    $shell = (string) file_get_contents(base_path('resources/js/AppShell.tsx'));

    expect($shell)
        ->toContain("root.setAttribute('data-theme', resolvedColorScheme)")
        ->toContain("root.classList.toggle('dark', resolvedColorScheme === 'dark')");
});
