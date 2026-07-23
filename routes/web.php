<?php

use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'dashboard/settings/profile');
    Route::redirect('settings/profile', 'dashboard/settings/profile');
    Route::redirect('settings/password', 'dashboard/settings/password');
    Route::redirect('settings/appearance', 'dashboard/settings/appearance');

    Route::prefix('dashboard/settings')->name('dashboard.settings.')->group(function () {
        Route::get('profile', [ProfileController::class, 'show'])->name('profile');
        Route::get('password', [PasswordController::class, 'show'])->name('password');
        Route::get('appearance', [AppearanceController::class, 'show'])->name('appearance');
        Route::patch('appearance', [AppearanceController::class, 'update'])->name('appearance.update');
        Route::get('two-factor', [TwoFactorController::class, 'show'])->name('two-factor');
    });
});

require __DIR__.'/auth.php';
