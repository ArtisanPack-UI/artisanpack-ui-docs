<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

function fakeRegistries(): void
{
    Http::fake([
        'packagist.org/packages/artisanpack-ui/forms.json' => Http::response([
            'package' => [
                'versions' => [
                    'dev-main' => [],
                    '2.1.0-beta.1' => [],
                    'v2.0.10' => [],
                    'v2.0.9' => [],
                    '1.4.0' => [],
                ],
            ],
        ]),
        'packagist.org/packages/artisanpack-ui/core.json' => Http::response([
            'package' => ['versions' => ['1.0.0' => [], 'dev-main' => []]],
        ]),
        'packagist.org/packages/artisanpack-ui/missing.json' => Http::response([], 404),
        'registry.npmjs.org/@artisanpack-ui%2Freact' => Http::response([
            'dist-tags' => ['latest' => '3.2.0', 'next' => '4.0.0-rc.1'],
            'versions' => ['3.1.0' => [], '3.2.0' => [], '4.0.0-rc.1' => []],
        ]),
        'registry.npmjs.org/@artisanpack-ui%2Fvue' => Http::response([
            'dist-tags' => ['latest' => '2.0.0-beta.2'],
            'versions' => ['1.9.0' => [], '1.10.1' => [], '2.0.0-beta.2' => []],
        ]),
    ]);
}

test('updates packagist and npm packages to their latest stable release', function () {
    fakeRegistries();
    Log::spy();

    $forms = Package::factory()->create(['slug' => 'forms', 'package_registry' => 'packagist', 'version' => '2.0.9']);
    $react = Package::factory()->create(['slug' => 'react', 'package_registry' => 'npm', 'version' => '3.1.0']);
    $vue = Package::factory()->create(['slug' => 'vue', 'package_registry' => 'npm', 'version' => null]);

    $this->artisan('packages:sync-versions')
        ->expectsOutputToContain('Updated 3 package version(s).')
        ->assertSuccessful();

    expect($forms->fresh()->version)->toBe('2.0.10')
        ->and($react->fresh()->version)->toBe('3.2.0')
        ->and($vue->fresh()->version)->toBe('1.10.1');

    Log::shouldHaveReceived('info')->times(3);
});

test('leaves unchanged and unregistered packages alone', function () {
    fakeRegistries();
    Log::spy();

    $core = Package::factory()->create(['slug' => 'core', 'package_registry' => 'packagist', 'version' => '1.0.0']);
    $local = Package::factory()->create(['slug' => 'local', 'package_registry' => null, 'version' => '0.1.0']);
    $coreUpdatedAt = $core->updated_at;

    $this->travel(1)->minutes();

    $this->artisan('packages:sync-versions')
        ->expectsOutputToContain('Updated 0 package version(s).')
        ->assertSuccessful();

    expect($core->fresh()->updated_at->equalTo($coreUpdatedAt))->toBeTrue()
        ->and($local->fresh()->version)->toBe('0.1.0');

    Log::shouldNotHaveReceived('info');
    Http::assertSentCount(1);
});

test('warns and skips packages whose registry lookup fails', function () {
    fakeRegistries();

    $missing = Package::factory()->create(['slug' => 'missing', 'package_registry' => 'packagist', 'version' => '1.0.0']);

    $this->artisan('packages:sync-versions')
        ->expectsOutputToContain('Could not determine the latest stable version of artisanpack-ui/missing.')
        ->assertSuccessful();

    expect($missing->fresh()->version)->toBe('1.0.0');
});

test('skips packages whose registry has no stable release', function () {
    Http::fake([
        'packagist.org/*' => Http::response(['package' => ['versions' => ['dev-main' => [], '1.0.0-alpha' => []]]]),
    ]);

    $package = Package::factory()->create(['slug' => 'early', 'package_registry' => 'packagist', 'version' => null]);

    $this->artisan('packages:sync-versions')
        ->expectsOutputToContain('Could not determine the latest stable version')
        ->assertSuccessful();

    expect($package->fresh()->version)->toBeNull();
});

test('dry run reports changes without saving them', function () {
    fakeRegistries();

    $forms = Package::factory()->create(['name' => 'Forms', 'slug' => 'forms', 'package_registry' => 'packagist', 'version' => '2.0.9']);

    $this->artisan('packages:sync-versions', ['--dry-run' => true])
        ->expectsOutputToContain('Forms: 2.0.9 → 2.0.10')
        ->expectsOutputToContain('Dry run complete; no versions were saved.')
        ->assertSuccessful();

    expect($forms->fresh()->version)->toBe('2.0.9');
});

test('the version sync is scheduled daily and can be disabled', function () {
    // `withSchedule()` callbacks register when the Artisan application boots.
    $this->artisan('schedule:list')->assertSuccessful();

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'packages:sync-versions'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');

    config()->set('artisanpack.packages.version_sync_enabled', true);
    expect($event->filtersPass(app()))->toBeTrue();

    config()->set('artisanpack.packages.version_sync_enabled', false);
    expect($event->filtersPass(app()))->toBeFalse();
});
