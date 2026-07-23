<?php

declare(strict_types=1);

use App\Http\HandleInertiaRequests;
use Illuminate\Http\Request;

it('shares flash keys on every Inertia response', function () {
    $request = Request::create('/');
    $request->setLaravelSession(app('session.store'));
    $request->session()->flash('success', 'Saved!');
    $request->session()->flash('error', 'Boom.');
    $request->session()->flash('info', 'FYI');
    $request->session()->flash('warning', 'Careful');

    $shared = app(HandleInertiaRequests::class)->share($request);

    expect($shared)->toHaveKey('flash');

    $flash = collect($shared['flash'])->map(fn ($value) => is_callable($value) ? $value() : $value)->all();

    expect($flash)->toMatchArray([
        'success' => 'Saved!',
        'error' => 'Boom.',
        'info' => 'FYI',
        'warning' => 'Careful',
    ]);
});

it('exposes the SharedProps TypeScript contract that mirrors the middleware', function () {
    $contract = (string) file_get_contents(base_path('resources/js/types/inertia.d.ts'));

    expect($contract)
        ->toContain("declare module '@inertiajs/core'")
        ->toContain('sharedPageProps: SharedProps')
        ->toContain('interface SharedProps')
        ->toContain('flash: FlashData');
});

it('provides a PageProps helper for per-page contracts', function () {
    $contract = (string) file_get_contents(base_path('resources/js/types/inertia.d.ts'));

    expect($contract)->toContain('export type PageProps');
});
