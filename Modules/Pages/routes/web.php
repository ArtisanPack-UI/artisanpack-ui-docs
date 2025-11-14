<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\Http\Controllers\PagesController;
use Modules\Pages\Livewire\Admin\AddPage;
use Modules\Pages\Livewire\Admin\EditPage;
use Modules\Pages\Livewire\Admin\Pages;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pages', PagesController::class)->names('pages');

	Route::get('/dashboard/pages/add-pages/', AddPage::class)->name('dashboard.pages.add');
	Route::get('/dashboard/pages/{page}', EditPage::class)->name('dashboard.pages.edit');
	Route::get('/dashboard/pages/', Pages::class)->name('dashboard.pages');
});
