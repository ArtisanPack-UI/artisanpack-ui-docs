<?php

use App\Http\Controllers\Settings\TwoFactorController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('dashboard/settings/two-factor', [TwoFactorController::class, 'show'])
        ->name('dashboard.settings.two-factor');
});

require __DIR__.'/auth.php';
