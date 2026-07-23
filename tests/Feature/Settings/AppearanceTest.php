<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('renders the Inertia appearance page for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard.settings.appearance'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/Appearance')
        ->where('theme', $user->theme_preference ?? 'system')
    );
});

it('redirects guests away from the appearance page', function () {
    $this->get(route('dashboard.settings.appearance'))->assertRedirect(route('login'));
});

it('persists the theme preference on the user profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('dashboard.settings.appearance'))
        ->patch(route('dashboard.settings.appearance.update'), ['theme' => 'dark']);

    $response->assertRedirect(route('dashboard.settings.appearance'));
    $response->assertSessionHas('status', 'appearance-updated');

    expect($user->refresh()->theme_preference)->toBe('dark');
});

it('accepts light, dark, and system as valid themes', function (string $theme) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard.settings.appearance'))
        ->patch(route('dashboard.settings.appearance.update'), ['theme' => $theme])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->theme_preference)->toBe($theme);
})->with(['light', 'dark', 'system']);

it('redirects the legacy /settings/* paths to /dashboard/settings/*', function (string $from, string $to) {
    $user = User::factory()->create();

    $this->actingAs($user)->get($from)->assertRedirect($to);
})->with([
    ['/settings', '/dashboard/settings/profile'],
    ['/settings/profile', '/dashboard/settings/profile'],
    ['/settings/password', '/dashboard/settings/password'],
    ['/settings/appearance', '/dashboard/settings/appearance'],
]);

it('rejects unknown themes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard.settings.appearance'))
        ->patch(route('dashboard.settings.appearance.update'), ['theme' => 'neon'])
        ->assertSessionHasErrors('theme');

    expect($user->refresh()->theme_preference)->toBe('system');
});
