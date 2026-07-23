<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia;

function confirmPassword(): void
{
    session()->put('auth.password_confirmed_at', time());
}

it('renders the two-factor settings page with disabled state for new users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard.settings.two-factor'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/TwoFactor')
        ->where('enabled', false)
        ->where('confirmed', false)
        ->where('qrCodeSvg', null)
        ->where('secretKey', null)
        ->where('recoveryCodes', [])
    );
});

it('renders the QR code and secret when two-factor is enabled but not confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => Crypt::encrypt('SECRET-KEY-VALUE'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['code-a', 'code-b'])),
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.settings.two-factor'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/TwoFactor')
        ->where('enabled', true)
        ->where('confirmed', false)
        ->where('secretKey', 'SECRET-KEY-VALUE')
        ->where('recoveryCodes', [])
        ->has('qrCodeSvg')
    );
});

it('exposes recovery codes once two-factor is confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => Crypt::encrypt('SECRET-KEY-VALUE'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['code-a', 'code-b'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.settings.two-factor'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/TwoFactor')
        ->where('enabled', true)
        ->where('confirmed', true)
        ->where('qrCodeSvg', null)
        ->where('secretKey', null)
        ->where('recoveryCodes', ['code-a', 'code-b'])
    );
});

it('redirects guests away from the two-factor settings page', function () {
    $response = $this->get(route('dashboard.settings.two-factor'));

    $response->assertRedirect(route('login'));
});

it('enables two-factor authentication via Fortify', function () {
    $user = User::factory()->create();
    confirmPassword();

    $response = $this->actingAs($user)->post('/user/two-factor-authentication');

    $response->assertRedirect();
    expect($user->fresh()->two_factor_secret)->not->toBeNull();
    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

it('regenerates recovery codes via Fortify', function () {
    $user = User::factory()->create([
        'two_factor_secret' => Crypt::encrypt('SECRET-KEY-VALUE'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['original-a', 'original-b'])),
        'two_factor_confirmed_at' => now(),
    ]);
    confirmPassword();

    $response = $this->actingAs($user)->post('/user/two-factor-recovery-codes');

    $response->assertRedirect();
    $codes = $user->fresh()->recoveryCodes();
    expect($codes)->not->toEqual(['original-a', 'original-b']);
    expect(count($codes))->toBe(8);
});

it('disables two-factor authentication via Fortify', function () {
    $user = User::factory()->create([
        'two_factor_secret' => Crypt::encrypt('SECRET-KEY-VALUE'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['code-a'])),
        'two_factor_confirmed_at' => now(),
    ]);
    confirmPassword();

    $response = $this->actingAs($user)->delete('/user/two-factor-authentication');

    $response->assertRedirect();
    expect($user->fresh()->two_factor_secret)->toBeNull();
    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});
