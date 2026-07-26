<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

it('renders the two-factor challenge page when a login is in progress', function () {
    $user = User::factory()->create();

    $response = $this
        ->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->get(route('two-factor.login'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/TwoFactorChallenge')
    );
});

it('redirects to the login page when no login is in progress', function () {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

it('returns an Inertia external redirect after a successful two-factor challenge', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-abc-123'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this
        ->withSession(['login.id' => $user->getKey(), 'login.remember' => false])
        ->post('/two-factor-challenge', [
            'recovery_code' => 'recovery-abc-123',
        ], ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest']);

    $response->assertStatus(409);
    $response->assertHeader('X-Inertia-Location', url(config('fortify.home')));
    $this->assertAuthenticatedAs($user);
});
