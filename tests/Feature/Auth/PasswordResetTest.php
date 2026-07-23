<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Auth\Livewire\Auth\ForgotPassword as ForgotPasswordComponent;
use Modules\Auth\Livewire\Auth\ResetPassword as ResetPasswordComponent;

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(ForgotPasswordComponent::class)
        ->set('email', $user->email)
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(ForgotPasswordComponent::class)
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        Livewire::test(ResetPasswordComponent::class, ['token' => $notification->token])
            ->set('email', $user->email)
            ->set('password', 'Str0ng-Passw0rd!')
            ->set('password_confirmation', 'Str0ng-Passw0rd!')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        return true;
    });
});
