<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\ImportChangelogController;
use Modules\Packages\Http\Controllers\ImportDocumentationController;

/*
 * The v1 CRUD + reorder routes for packages, documentation, and
 * changelogs now live in the app-level `routes/api.php` (registered
 * in bootstrap/app.php) so all Sanctum-guarded endpoints — with
 * their abilities middleware — sit together.
 *
 * Only the import triggers stay module-local for now because they're
 * tightly coupled to the `ImportWikiDocumentation` / `ImportChangelog`
 * jobs. Both require the `imports:trigger` ability and inherit the
 * `throttle:api` limiter from the `api` middleware group.
 */
Route::middleware(['auth:sanctum', 'abilities:imports:trigger'])->prefix('v1')->group(function () {
    Route::post('packages/{package}/import-docs', ImportDocumentationController::class)
        ->name('packages.import-docs');
    Route::post('packages/{package}/import-changelog', ImportChangelogController::class)
        ->name('packages.import-changelog');
});
