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
});
