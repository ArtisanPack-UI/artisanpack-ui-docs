<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\Admin\DocumentationController as AdminDocumentationController;
use Modules\Packages\Http\Controllers\Admin\ImportChangelogController as AdminImportChangelogController;
use Modules\Packages\Http\Controllers\Admin\ImportDocumentationController as AdminImportDocumentationController;
use Modules\Packages\Http\Controllers\Admin\PackagesController as AdminPackagesController;
use Modules\Packages\Http\Controllers\ChangelogViewerController;
use Modules\Packages\Http\Controllers\ComponentDocsController;
use Modules\Packages\Http\Controllers\DocumentationReorderController;
use Modules\Packages\Http\Controllers\DocumentationViewerController;

// Redirect old changelog documentation pages to changelogs section
Route::get('/documentation/{package}/changelog', function ($package) {
    return redirect()->route('changelog.show', ['package' => $package], 301);
});

Route::get('/documentation/{package}/changelogs', function ($package) {
    return redirect()->route('changelog.show', ['package' => $package], 301);
});

// Design-system component pages. Registered before the catch-all docs
// route below, whose `.*` slug would otherwise swallow this path. A slug
// with no matching manifest is handed to the docs viewer by the controller.
Route::get('/documentation/design-system/components/{component}', [ComponentDocsController::class, 'show'])
    ->where('component', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('documentation.design-system.component');

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

    Route::get('/dashboard/packages/{package}/documentation', [AdminDocumentationController::class, 'index'])
        ->name('dashboard.packages.documentation');
    Route::post('/dashboard/packages/{package}/documentation/reorder', DocumentationReorderController::class)
        ->middleware('throttle:60,1')
        ->name('dashboard.packages.documentation.reorder');

    Route::get('/dashboard/packages/{package}', [AdminPackagesController::class, 'edit'])->name('dashboard.packages.edit');
    Route::patch('/dashboard/packages/{package}', [AdminPackagesController::class, 'update'])->name('dashboard.packages.update');
    Route::delete('/dashboard/packages/{package}', [AdminPackagesController::class, 'destroy'])->name('dashboard.packages.destroy');

    Route::post('/dashboard/packages/{package}/import-documentation', AdminImportDocumentationController::class)
        ->middleware('throttle:10,1')
        ->name('dashboard.packages.import-documentation');
    Route::post('/dashboard/packages/{package}/import-changelog', AdminImportChangelogController::class)
        ->middleware('throttle:10,1')
        ->name('dashboard.packages.import-changelog');
});
