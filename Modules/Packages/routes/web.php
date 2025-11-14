<?php

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\PackagesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('packages', PackagesController::class)->names('packages');
});
