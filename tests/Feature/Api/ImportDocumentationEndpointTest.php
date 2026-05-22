<?php

use App\Jobs\ImportWikiDocumentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\ImportDocumentationController;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

test('the import-docs route is registered and protected by authentication', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($route) => $route->getName() === 'api.packages.import-docs'
    );

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain('POST')
        ->and($route->uri())->toBe('api/v1/packages/{package}/import-docs')
        ->and($route->gatherMiddleware())->toContain('auth:sanctum');
});

test('the endpoint queues an import for a package with a docs url', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'slug' => 'core',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    $response = (new ImportDocumentationController)($package);

    expect($response->getStatusCode())->toBe(202)
        ->and($response->getData(true))->toMatchArray([
            'package' => 'core',
            'source' => 'docs',
        ]);

    Queue::assertPushed(ImportWikiDocumentation::class, fn ($job) => $job->package->id === $package->id);
});

test('the endpoint reports the wiki source when only a wiki url is set', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => 'https://github.com/owner/repo/wiki',
        'docs_url' => null,
    ]);

    $response = (new ImportDocumentationController)($package);

    expect($response->getStatusCode())->toBe(202)
        ->and($response->getData(true)['source'])->toBe('wiki');

    Queue::assertPushed(ImportWikiDocumentation::class);
});

test('the endpoint rejects a package without any source url', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => null,
        'docs_url' => null,
    ]);

    $response = (new ImportDocumentationController)($package);

    expect($response->getStatusCode())->toBe(422);

    Queue::assertNothingPushed();
});
