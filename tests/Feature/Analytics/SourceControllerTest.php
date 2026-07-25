<?php

declare(strict_types=1);

use App\Analytics\AnalyticsSource;
use App\Analytics\AnalyticsSourceResolver;
use App\Enums\Role;
use App\Models\User;
use Modules\Core\Setting;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('requires authentication', function () {
    post(route('dashboard.analytics.source.update'), ['source' => 'local'])
        ->assertRedirect(route('login'));
});

it('rejects non-admin users via the view-analytics gate', function () {
    $editor = User::factory()->create([
        'role' => Role::Editor,
        'email_verified_at' => now(),
    ]);

    actingAs($editor)
        ->post(route('dashboard.analytics.source.update'), ['source' => 'local'])
        ->assertForbidden();
});

it('rejects an unknown source value', function () {
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
    ]);

    actingAs($admin)
        ->post(route('dashboard.analytics.source.update'), ['source' => 'segment'])
        ->assertSessionHasErrors('source');
});

it('persists the local source to Setting', function () {
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
    ]);

    actingAs($admin)
        ->post(route('dashboard.analytics.source.update'), ['source' => 'local'])
        ->assertSessionHas('success');

    expect(Setting::query()->where('key', AnalyticsSource::SETTING_KEY)->value('value'))
        ->toBe('local');
});

it('refuses google when no google connection is available', function () {
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
    ]);

    // Force the resolver to report Google as unavailable regardless of the
    // (empty) test DB state so the assertion is deterministic.
    $this->mock(AnalyticsSourceResolver::class, function ($mock) {
        $mock->shouldReceive('isGoogleAvailable')->andReturn(false);
        $mock->shouldNotReceive('set');
    });

    actingAs($admin)
        ->post(route('dashboard.analytics.source.update'), ['source' => 'google'])
        ->assertSessionHas('error');

    expect(Setting::query()->where('key', AnalyticsSource::SETTING_KEY)->exists())->toBeFalse();
});

it('accepts google when a connection is available', function () {
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
    ]);

    $this->mock(AnalyticsSourceResolver::class, function ($mock) {
        $mock->shouldReceive('isGoogleAvailable')->andReturn(true);
        $mock->shouldReceive('set')->once()->with(AnalyticsSource::Google);
    });

    actingAs($admin)
        ->post(route('dashboard.analytics.source.update'), ['source' => 'google'])
        ->assertSessionHas('success');
});
