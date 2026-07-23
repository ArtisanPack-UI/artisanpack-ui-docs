<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('renders the Inertia login page at /login', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/Login')
        ->where('canResetPassword', true)
        ->has('status')
    );
});

it('logs users in via the Fortify POST /login action', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
    ]);

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials with a validation error on email', function () {
    $user = User::factory()->create();

    $response = $this->from(route('login'))->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});
