<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    config(['app.debug' => false]);
});

it('renders the designed 404 Inertia page for missing routes', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Errors/NotFound')
        ->where('status', 404)
    );
});

it('renders the designed 500 Inertia page for server errors', function () {
    $app = app();
    $app->make('router')
        ->middleware('web')
        ->get('/up/boom', function () {
            throw new RuntimeException('Boom');
        });
    $app->make('router')->getRoutes()->refreshNameLookups();

    $response = $this->get('/up/boom');

    $response->assertStatus(500);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Errors/ServerError')
        ->where('status', 500)
    );
});

it('still renders the designed 404 page when debug mode is on', function () {
    config(['app.debug' => true]);

    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound();
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('Errors/NotFound'));
});

it('renders the designed 500 page for 503 service-unavailable responses', function () {
    $app = app();
    $app->make('router')
        ->middleware('web')
        ->get('/up/unavailable', function () {
            abort(503);
        });
    $app->make('router')->getRoutes()->refreshNameLookups();

    $response = $this->get('/up/unavailable');

    $response->assertStatus(503);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Errors/ServerError')
        ->where('status', 503)
    );
});

it('returns JSON error responses for API requests instead of Inertia pages', function () {
    $response = $this->getJson('/api/does-not-exist');

    $response->assertNotFound();
    expect($response->headers->get('content-type'))->toContain('json');
});
