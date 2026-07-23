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
