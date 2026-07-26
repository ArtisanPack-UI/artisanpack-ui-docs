<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\HomePageController;
use Modules\Core\Http\Controllers\SearchController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cores', CoreController::class)->names('core');

    Route::get('/dashboard/settings', [AdminSettingsController::class, 'show'])->name('dashboard.settings');
    Route::patch('/dashboard/settings', [AdminSettingsController::class, 'update'])->name('dashboard.settings.update');
});

Route::get('/', [HomePageController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
