<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ChangelogController;
use App\Http\Controllers\Api\V1\DocumentationController;
use App\Http\Controllers\Api\V1\PackageController;
use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\DocumentationReorderController;

/*
 * v1 remote-admin API (V2_REFACTOR_PLAN.md §4.2, §8.2, §9.6).
 *
 * Every route is Sanctum-guarded and gated by one of the bounded
 * abilities in `App\Enums\TokenAbility`. Read routes require a
 * `*:read` ability; write routes require the matching `*:write`
 * ability. Model-level authorization is still enforced by the
 * controllers via policies so a valid token can't bypass business
 * rules (e.g. only admins may create/delete).
 */
Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('abilities:packages:read')->group(function () {
        Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
        Route::get('packages/{package}', [PackageController::class, 'show'])->name('packages.show');
    });

    Route::middleware('abilities:packages:write')->group(function () {
        Route::post('packages', [PackageController::class, 'store'])->name('packages.store');
        Route::patch('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
    });

    Route::middleware('abilities:docs:read')->group(function () {
        Route::get('packages/{package}/documentation', [DocumentationController::class, 'index'])
            ->name('packages.documentation.index');
        Route::get('documentation/{documentation}', [DocumentationController::class, 'show'])
            ->name('documentation.show');
    });

    Route::middleware('abilities:docs:write')->group(function () {
        Route::post('packages/{package}/documentation', [DocumentationController::class, 'store'])
            ->name('packages.documentation.store');
        Route::patch('documentation/{documentation}', [DocumentationController::class, 'update'])
            ->name('documentation.update');
        Route::delete('documentation/{documentation}', [DocumentationController::class, 'destroy'])
            ->name('documentation.destroy');
        Route::post('packages/{package}/documentation/reorder', DocumentationReorderController::class)
            ->middleware('throttle:60,1')
            ->name('packages.documentation.reorder');
    });

    Route::middleware('abilities:changelogs:read')->group(function () {
        Route::get('packages/{package}/changelogs', [ChangelogController::class, 'index'])
            ->name('packages.changelogs.index');
    });

    Route::middleware('abilities:changelogs:write')->group(function () {
        Route::post('packages/{package}/changelogs', [ChangelogController::class, 'store'])
            ->name('packages.changelogs.store');
        Route::patch('changelogs/{changelog}', [ChangelogController::class, 'update'])
            ->name('changelogs.update');
        Route::delete('changelogs/{changelog}', [ChangelogController::class, 'destroy'])
            ->name('changelogs.destroy');
    });
});
