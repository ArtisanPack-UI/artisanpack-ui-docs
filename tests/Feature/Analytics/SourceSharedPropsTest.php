<?php

declare(strict_types=1);

use App\Analytics\AnalyticsSource;
use App\Analytics\AnalyticsSourceResolver;
use App\Enums\Role;
use App\Http\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Core\Setting;

function resolvedAnalyticsSourceProps(User $user): mixed
{
    $request = Request::create('/dashboard/analytics');
    $request->setUserResolver(fn () => $user);

    $shared = app(HandleInertiaRequests::class)->share($request);

    $value = $shared['analyticsSource'] ?? null;

    return is_callable($value) ? $value() : $value;
}

it('does not expose the analyticsSource prop to editors', function () {
    $editor = User::factory()->create([
        'role' => Role::Editor,
        'email_verified_at' => now(),
    ]);

    expect(resolvedAnalyticsSourceProps($editor))->toBeNull();
});

it('exposes the current source and options to admins', function () {
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
    ]);

    Setting::updateOrCreate(
        ['key' => AnalyticsSource::SETTING_KEY],
        ['value' => AnalyticsSource::Google->value],
    );

    // Deterministic availability signal so we don't accidentally hit GA
    // credentials or the connections table in the assertion.
    $this->mock(AnalyticsSourceResolver::class, function ($mock) {
        $mock->shouldReceive('active')->andReturn(AnalyticsSource::Google);
        $mock->shouldReceive('isGoogleAvailable')->andReturn(true);
    });

    $props = resolvedAnalyticsSourceProps($admin);

    expect($props)->toMatchArray([
        'active' => 'google',
        'options' => [
            'local' => 'Local (first-party)',
            'google' => 'Google Analytics',
        ],
        'google_available' => true,
    ])
        ->and($props['update_url'])->toBe(route('dashboard.analytics.source.update'));
});

it('defaults to local when no setting has been persisted yet', function () {
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
    ]);

    $props = resolvedAnalyticsSourceProps($admin);

    expect($props['active'])->toBe('local');
});
