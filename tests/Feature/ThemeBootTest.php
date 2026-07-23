<?php

declare(strict_types=1);

it('ships a shared theme boot partial with a synchronous IIFE', function () {
    $partial = (string) file_get_contents(base_path('resources/views/partials/theme-boot.blade.php'));

    expect($partial)
        ->toContain('<script>')
        ->toContain('(function ()')
        ->toContain("localStorage.getItem('theme')")
        ->toContain('prefers-color-scheme: dark')
        ->toContain("root.setAttribute('data-theme', theme)")
        ->toContain("root.classList.toggle('dark', theme === 'dark')");
});

it('includes the shared theme boot partial from every Blade root', function () {
    $inertiaRoot = (string) file_get_contents(base_path('resources/views/app.blade.php'));
    $authHead = (string) file_get_contents(base_path('resources/views/partials/head.blade.php'));
    $livewireHead = (string) file_get_contents(base_path('Modules/Core/resources/views/partials/head.blade.php'));

    expect($inertiaRoot)->toContain("@include('partials.theme-boot')");
    expect($authHead)->toContain("@include('partials.theme-boot')");
    expect($livewireHead)->toContain("@include('partials.theme-boot')");
});

it('does not duplicate the inline theme boot script outside the shared partial', function () {
    $roots = [
        base_path('resources/views/app.blade.php'),
        base_path('resources/views/partials/head.blade.php'),
        base_path('Modules/Core/resources/views/partials/head.blade.php'),
    ];

    foreach ($roots as $path) {
        $contents = (string) file_get_contents($path);

        expect($contents)->not->toContain("localStorage.getItem('theme') === 'dark'");
    }
});
