<?php

declare(strict_types=1);

use Illuminate\Http\Request;
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

    Route::get('forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    })->name('password.request');

    Route::get('reset-password/{token}', function (string $token, Request $request) {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    })->name('password.reset');
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
