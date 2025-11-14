<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Livewire\Dashboard;
use Modules\Admin\Livewire\SettingsPage;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admins', AdminController::class)->names('admin');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/dashboard/settings', SettingsPage::class)->name('settings');
});
