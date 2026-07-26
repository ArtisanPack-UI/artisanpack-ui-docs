<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

it('renders the Inertia forgot-password page for guests', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/ForgotPassword')
        ->has('status')
    );
});

it('sends the password reset link via Fortify POST /forgot-password', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->from(route('password.request'))
        ->post('/forgot-password', ['email' => $user->email]);

    $response->assertRedirect(route('password.request'));
    $response->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);
});

it('renders the Inertia reset-password page with token and email', function () {
    $response = $this->get(route('password.reset', ['token' => 'test-token']).'?email=user@example.com');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/ResetPassword')
        ->where('token', 'test-token')
        ->where('email', 'user@example.com')
    );
});

it('resets the password with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->from(route('password.request'))
        ->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');

        return true;
    });
});

it('rejects a password reset with an invalid token', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $response = $this->from(route('password.reset', ['token' => 'invalid-token']))
        ->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

    $response->assertSessionHasErrors('email');
    expect($user->fresh()->password)->toBe($originalPassword);
});
