<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\Http\Controllers\PagesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pages', PagesController::class)->names('pages');
});
