<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::middleware('guest')->group(function () {
    Route::get('login', function () {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => session('status'),
        ]);
    })->name('login');

    Route::get('two-factor-challenge', function () {
        if (! session()->has('login.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    })->name('two-factor.login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('email/verify', function () {
        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
        ]);
    })->name('verification.notice');

    Route::get('verified', function () {
        return Inertia::render('Auth/Verified');
    })->middleware('verified')->name('verification.verified');

    Route::get('user/confirm-password', function () {
        return Inertia::render('Auth/ConfirmPassword');
    })->name('password.confirm');
});
