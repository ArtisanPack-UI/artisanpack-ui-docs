<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\ImportDocumentationController;
use Modules\Packages\Http\Controllers\PackageController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('packages', PackageController::class)->names('packages');
    Route::post('packages/{package}/import-docs', ImportDocumentationController::class)
        ->name('packages.import-docs');
});
