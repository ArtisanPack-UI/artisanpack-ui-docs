<?php

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\PackagesController;
use Modules\Packages\Livewire\Admin\AddPackage;
use Modules\Packages\Livewire\Admin\EditPackage;
use Modules\Packages\Livewire\Admin\Packages;
use Modules\Packages\Livewire\Public\Documentation;

// Public documentation route - test route
Route::get('/documentation/test', function () {
    return 'Documentation route is working!';
});

// Public documentation route
Route::get('/documentation/{package}/{slug}', Documentation::class)
    ->where('slug', '.*')
    ->name('documentation.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('packages', PackagesController::class)->names('packages');

    Route::get('/dashboard/packages/add-package/', AddPackage::class)->name('dashboard.packages.add');
    Route::get('/dashboard/packages/{package}', EditPackage::class)->name('dashboard.packages.edit');
    Route::get('/dashboard/packages/', Packages::class)->name('dashboard.packages');
});
