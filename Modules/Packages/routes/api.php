<?php

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\ImportDocumentationController;
use Modules\Packages\Http\Controllers\PackagesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('packages', PackagesController::class)->names('packages');
    Route::post('packages/{package}/import-docs', ImportDocumentationController::class)
        ->name('packages.import-docs');
});
