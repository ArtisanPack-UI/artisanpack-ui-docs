<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\HomePageController;
use Modules\Core\Http\Controllers\SearchController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cores', CoreController::class)->names('core');
});

Route::get('/', [HomePageController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
