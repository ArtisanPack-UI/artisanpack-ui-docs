<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pages\Http\Controllers\PageController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('pages', PageController::class)->names('pages');
});
