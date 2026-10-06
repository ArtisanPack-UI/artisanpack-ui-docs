<?php

use App\Enums\ImportStatus;
use App\Jobs\ImportChangelog;
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

test('the import-changelog route is registered behind auth, the imports:trigger ability, and the api throttle', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($route) => $route->getName() === 'api.packages.import-changelog'
    );

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain('POST')
        ->and($route->uri())->toBe('api/v1/packages/{package}/import-changelog')
        ->and($route->gatherMiddleware())->toContain('api')
        ->and($route->gatherMiddleware())->toContain('auth:sanctum')
        ->and($route->gatherMiddleware())->toContain('abilities:imports:trigger');
});

test('the endpoint queues a changelog import and marks it queued', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'slug' => 'core',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
        'changelog_import_error' => 'previous failure',
    ]);

    $this->postJson(route('api.packages.import-changelog', $package))
        ->assertAccepted()
        ->assertJson([
            'message' => 'Changelog import queued.',
            'package' => 'core',
        ]);

    Queue::assertPushed(ImportChangelog::class, fn ($job) => $job->package->id === $package->id);

    $package->refresh();
    expect($package->changelog_import_status)->toBe(ImportStatus::Queued)
        ->and($package->changelog_import_error)->toBeNull();
});

test('the endpoint accepts a package slug in place of its id', function () {
    Queue::fake();

    Package::factory()->create([
        'slug' => 'forms',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $this->postJson('/api/v1/packages/forms/import-changelog')
        ->assertAccepted()
        ->assertJsonPath('package', 'forms');

    Queue::assertPushed(ImportChangelog::class);
});

test('the endpoint rejects a package without a changelog url', function () {
    Queue::fake();

    $package = Package::factory()->create(['changelog_url' => '']);

    $this->postJson(route('api.packages.import-changelog', $package))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'The package does not have a changelog URL configured.');

    Queue::assertNothingPushed();
});

test('the endpoint returns 404 for an unknown package', function () {
    Queue::fake();

    $this->postJson('/api/v1/packages/999999/import-changelog')->assertNotFound();
    $this->postJson('/api/v1/packages/no-such-package/import-changelog')->assertNotFound();

    Queue::assertNothingPushed();
});

test('the endpoint rejects a token without the imports:trigger ability', function () {
    Queue::fake();
    Sanctum::actingAs(User::factory()->create(), ['changelogs:write', 'packages:read']);

    $package = Package::factory()->create();

    $this->postJson(route('api.packages.import-changelog', $package))->assertForbidden();

    Queue::assertNothingPushed();
});

test('the endpoint rejects unauthenticated requests', function () {
    Queue::fake();
    $this->app['auth']->forgetGuards();

    $package = Package::factory()->create();

    $this->postJson(route('api.packages.import-changelog', $package))->assertUnauthorized();

    Queue::assertNothingPushed();
});
