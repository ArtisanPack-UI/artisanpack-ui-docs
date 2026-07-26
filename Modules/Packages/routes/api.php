<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Packages\Http\Controllers\ImportDocumentationController;

/*
 * The v1 CRUD + reorder routes for packages, documentation, and
 * changelogs now live in the app-level `routes/api.php` (registered
 * in bootstrap/app.php) so all Sanctum-guarded endpoints — with
 * their abilities middleware — sit together.
 *
 * Only the docs-import trigger stays module-local for now because
 * it's tightly coupled to `Modules\Packages\Jobs\ImportWikiDocumentation`.
 */
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('packages/{package}/import-docs', ImportDocumentationController::class)
        ->name('packages.import-docs');
});
