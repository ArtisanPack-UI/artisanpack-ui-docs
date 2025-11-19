<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
