<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\Http\Controllers\PagesController;
use Modules\Pages\Http\Controllers\PageViewerController;
use Modules\Pages\Livewire\Admin\AddPage;
use Modules\Pages\Livewire\Admin\EditPage;
use Modules\Pages\Livewire\Admin\ManagePageOrder;
use Modules\Pages\Livewire\Admin\Pages;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pages', PagesController::class)->names('pages');

    Route::get('/dashboard/pages/add-page/', AddPage::class)->name('dashboard.pages.add');
    Route::get('/dashboard/pages/menu-order/', ManagePageOrder::class)->name('dashboard.pages.menu-order');
    Route::get('/dashboard/pages/{page}', EditPage::class)->name('dashboard.pages.edit');
    Route::get('/dashboard/pages/', Pages::class)->name('dashboard.pages');
});

/*
 * Reserved first-segment patterns.
 *
 * Kept as a shared negative lookahead so both the top-level and nested
 * page routes refuse to shadow admin, docs, changelogs, or any Fortify
 * auth surface even if an editor happens to create a `Page` with a
 * matching slug. Order-of-registration already guards static routes
 * that were declared earlier, but the constraint also prevents
 * `route('page.show', ['slug' => 'login'])` from producing a URL that
 * silently resolves to the login screen.
 */
$reservedFirstSegment = '^(?!(?:dashboard|documentation|changelogs|login|logout|forgot-password|reset-password|two-factor-challenge|email|verified|user|settings|sitemap|up|api|admins|cores|packages|pages|users|livewire|storage)(?:/|$)).+';

// Public page routes - placed at the end to avoid conflicts with other routes
Route::get('/{parentSlug}/{slug}', [PageViewerController::class, 'showChild'])
    ->where('parentSlug', $reservedFirstSegment)
    ->name('page.child');

Route::get('/{slug}', [PageViewerController::class, 'show'])
    ->where('slug', $reservedFirstSegment)
    ->name('page.show');
