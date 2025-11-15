<?php

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\PackagesController;
use Modules\Packages\Livewire\Admin\AddPackage;
use Modules\Packages\Livewire\Admin\EditPackage;
use Modules\Packages\Livewire\Admin\Packages;
use Modules\Pages\Livewire\Admin\AddPage;
use Modules\Pages\Livewire\Admin\EditPage;
use Modules\Pages\Livewire\Admin\Pages;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('packages', PackagesController::class)->names('packages');

    Route::get('/dashboard/packages/add-package/', AddPackage::class)->name('dashboard.packages.add');
    Route::get('/dashboard/packages/{package}', EditPackage::class)->name('dashboard.packages.edit');
    Route::get('/dashboard/packages/', Packages::class)->name('dashboard.packages');
});
