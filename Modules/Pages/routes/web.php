<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\Http\Controllers\PagesController;
use Modules\Pages\Livewire\Admin\AddPage;
use Modules\Pages\Livewire\Admin\EditPage;
use Modules\Pages\Livewire\Admin\ManagePageOrder;
use Modules\Pages\Livewire\Admin\Pages;
use Modules\Pages\Livewire\Public\Page;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pages', PagesController::class)->names('pages');

    Route::get('/dashboard/pages/add-page/', AddPage::class)->name('dashboard.pages.add');
    Route::get('/dashboard/pages/menu-order/', ManagePageOrder::class)->name('dashboard.pages.menu-order');
    Route::get('/dashboard/pages/{page}', EditPage::class)->name('dashboard.pages.edit');
    Route::get('/dashboard/pages/', Pages::class)->name('dashboard.pages');
});

// Public page routes - placed at the end to avoid conflicts with other routes
Route::get('/{parentSlug}/{slug}', Page::class)
    ->where('parentSlug', '^(?!documentation|changelogs).*')
    ->name('page.child');

Route::get('/{slug}', Page::class)
    ->where('slug', '.+')
    ->where('slug', '^(?!dashboard|login|register|password|changelogs).*')
    ->name('page.show');
