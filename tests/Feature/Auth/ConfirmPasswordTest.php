<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('renders the password confirmation page for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/ConfirmPassword')
    );
});

it('redirects guests to the login page', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});

it('confirms the password via Fortify POST /user/confirm-password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/user/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});
