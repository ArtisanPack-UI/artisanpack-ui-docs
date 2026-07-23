<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Http\Kernel;
use Inertia\Inertia;
use Inertia\Middleware;
use Inertia\Response as InertiaResponse;

it('registers HandleInertiaRequests on the web middleware group', function () {
    $middleware = app(Kernel::class)->getMiddlewareGroups()['web'] ?? [];

    expect($middleware)->toContain(HandleInertiaRequests::class);
});

it('extends the Inertia base middleware', function () {
    expect(is_subclass_of(HandleInertiaRequests::class, Middleware::class))->toBeTrue();
});

it('resolves Inertia responses to the app root view', function () {
    expect((new HandleInertiaRequests)->rootView(request()))->toBe('app');
});

it('renders the root Blade template as valid HTML for an Inertia page', function () {
    $html = Inertia::render('Welcome')
        ->toResponse(request())
        ->getContent();

    expect($html)
        ->toContain('<div id="app"')
        ->toContain('data-page=')
        ->toContain('resources/js/app.tsx');
});

it('returns an Inertia response object from the Inertia facade', function () {
    expect(Inertia::render('Welcome'))->toBeInstanceOf(InertiaResponse::class);
});
