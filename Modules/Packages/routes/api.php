<?php

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\PackagesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('packages', PackagesController::class)->names('packages');
});
