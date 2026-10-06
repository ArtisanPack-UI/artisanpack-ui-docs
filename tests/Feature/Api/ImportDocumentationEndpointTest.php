<?php

use App\Enums\ImportStatus;
use App\Jobs\ImportWikiDocumentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create(), ['imports:trigger']);
});

test('the import-docs route is registered and protected by authentication and the imports:trigger ability', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($route) => $route->getName() === 'api.packages.import-docs'
    );

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain('POST')
        ->and($route->uri())->toBe('api/v1/packages/{package}/import-docs')
        ->and($route->gatherMiddleware())->toContain('auth:sanctum')
        ->and($route->gatherMiddleware())->toContain('abilities:imports:trigger');
});

test('the endpoint queues an import for a package with a docs url', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'slug' => 'core',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    $response = $this->postJson(route('api.packages.import-docs', $package));

    $response->assertAccepted()
        ->assertJson([
            'package' => 'core',
            'source' => 'docs',
        ]);

    Queue::assertPushed(ImportWikiDocumentation::class, fn ($job) => $job->package->id === $package->id);

    expect($package->fresh()->docs_import_status)->toBe(ImportStatus::Queued);
});

test('the endpoint reports the wiki source when only a wiki url is set', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => 'https://github.com/owner/repo/wiki',
        'docs_url' => null,
    ]);

    $response = $this->postJson(route('api.packages.import-docs', $package));

    $response->assertAccepted()
        ->assertJsonPath('source', 'wiki');

    Queue::assertPushed(ImportWikiDocumentation::class);
});

test('the endpoint rejects a package without any source url', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => null,
        'docs_url' => null,
    ]);

    $response = $this->postJson(route('api.packages.import-docs', $package));

    $response->assertUnprocessable();

    Queue::assertNothingPushed();
    expect($package->fresh()->docs_import_status)->toBeNull();
});

test('the endpoint rejects a token without the imports:trigger ability', function (array $abilities) {
    Queue::fake();
    Sanctum::actingAs(User::factory()->create(), $abilities);

    $package = Package::factory()->create(['docs_url' => 'https://github.com/owner/repo']);

    $this->postJson(route('api.packages.import-docs', $package))->assertForbidden();

    Queue::assertNothingPushed();
})->with([
    'read-only token' => [['packages:read', 'docs:read']],
    'write token' => [['packages:write', 'docs:write']],
]);

test('the endpoint rejects unauthenticated requests', function () {
    Queue::fake();
    $this->app['auth']->forgetGuards();

    $package = Package::factory()->create(['docs_url' => 'https://github.com/owner/repo']);

    $this->postJson(route('api.packages.import-docs', $package))->assertUnauthorized();

    Queue::assertNothingPushed();
});
