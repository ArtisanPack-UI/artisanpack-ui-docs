<?php

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\TwoFactorAuthenticatable;

it('registers the three required Fortify features and disables registration', function () {
    $features = config('fortify.features');

    expect(Features::enabled(Features::emailVerification()))->toBeTrue();
    expect(Features::enabled(Features::resetPasswords()))->toBeTrue();
    expect(Features::enabled(Features::twoFactorAuthentication()))->toBeTrue();
    expect(Features::enabled(Features::registration()))->toBeFalse();
    expect(Features::enabled(Features::updateProfileInformation()))->toBeFalse();
    expect(Features::enabled(Features::updatePasswords()))->toBeFalse();
    expect(Features::canUpdateProfileInformation())->toBeFalse();

    expect(Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'))->toBeTrue();
    expect(Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'))->toBeTrue();
});

it('exposes the expected Fortify action routes for the enabled features', function () {
    $routes = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->all();

    expect($routes)->toContain('password.email');
    expect($routes)->toContain('password.update');
    expect($routes)->toContain('verification.verify');
    expect($routes)->toContain('verification.send');
    expect($routes)->toContain('two-factor.login.store');
    expect($routes)->toContain('two-factor.enable');
    expect($routes)->toContain('two-factor.confirm');
    expect($routes)->toContain('two-factor.disable');
});

it('leaves GET view routes to the app so v2 Livewire pages keep serving during the Inertia port', function () {
    $fortifyGetRoutes = collect(Route::getRoutes())
        ->filter(fn ($r) => str_contains($r->getActionName(), 'Laravel\\Fortify')
            && in_array('GET', $r->methods(), true))
        ->map(fn ($r) => $r->uri())
        ->values()
        ->all();

    expect($fortifyGetRoutes)->not->toContain('login');
    expect($fortifyGetRoutes)->not->toContain('forgot-password');
    expect($fortifyGetRoutes)->not->toContain('email/verify');
    expect($fortifyGetRoutes)->not->toContain('two-factor-challenge');
});

it('does not register any registration routes', function () {
    $routes = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->all();

    expect($routes)->not->toContain('register');
});

it('prepares the user model for Fortify auth', function () {
    $user = new User;

    expect($user)->toBeInstanceOf(MustVerifyEmail::class);
    expect(class_uses_recursive($user))->toContain(TwoFactorAuthenticatable::class);
    expect($user->getHidden())->toContain('two_factor_secret');
    expect($user->getHidden())->toContain('two_factor_recovery_codes');
});

it('adds the two-factor columns to the users table', function () {
    expect(Schema::hasColumns('users', [
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ]))->toBeTrue();
});
