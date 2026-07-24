<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\Admin\PackagesController as AdminPackagesController;
use Modules\Packages\Http\Controllers\ChangelogViewerController;
use Modules\Packages\Http\Controllers\DocumentationViewerController;
use Modules\Packages\Livewire\Admin\ManageDocumentation;

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
    Route::get('/dashboard/packages', [AdminPackagesController::class, 'index'])->name('dashboard.packages');
    Route::get('/dashboard/packages/add-package', [AdminPackagesController::class, 'create'])->name('dashboard.packages.add');
    Route::post('/dashboard/packages', [AdminPackagesController::class, 'store'])->name('dashboard.packages.store');
    Route::get('/dashboard/packages/{package}/documentation', ManageDocumentation::class)->name('dashboard.packages.documentation');
    Route::get('/dashboard/packages/{package}', [AdminPackagesController::class, 'edit'])->name('dashboard.packages.edit');
    Route::patch('/dashboard/packages/{package}', [AdminPackagesController::class, 'update'])->name('dashboard.packages.update');
    Route::delete('/dashboard/packages/{package}', [AdminPackagesController::class, 'destroy'])->name('dashboard.packages.destroy');
});
