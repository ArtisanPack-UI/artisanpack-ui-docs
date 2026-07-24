<?php

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\ChangelogViewerController;
use Modules\Packages\Http\Controllers\DocumentationViewerController;
use Modules\Packages\Http\Controllers\PackagesController;
use Modules\Packages\Livewire\Admin\AddPackage;
use Modules\Packages\Livewire\Admin\EditPackage;
use Modules\Packages\Livewire\Admin\ManageDocumentation;
use Modules\Packages\Livewire\Admin\Packages;

// Redirect old changelog documentation pages to changelogs section
Route::get('/documentation/{package}/changelog', function ($package) {
    return redirect()->route('changelog.show', ['package' => $package], 301);
});

Route::get('/documentation/{package}/changelogs', function ($package) {
    return redirect()->route('changelog.show', ['package' => $package], 301);
});

// Public documentation route
Route::get('/documentation/{package}/{slug}', [DocumentationViewerController::class, 'show'])
    ->where('slug', '.*')
    ->name('documentation.show');

// Public changelog route
Route::get('/changelogs/{package}', [ChangelogViewerController::class, 'show'])
    ->name('changelog.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('packages', PackagesController::class)->names('packages');

    Route::get('/dashboard/packages/add-package/', AddPackage::class)->name('dashboard.packages.add');
    Route::get('/dashboard/packages/{package}/documentation', ManageDocumentation::class)->name('dashboard.packages.documentation');
    Route::get('/dashboard/packages/{package}', EditPackage::class)->name('dashboard.packages.edit');
    Route::get('/dashboard/packages/', Packages::class)->name('dashboard.packages');
});
