<?php

declare(strict_types=1);

use App\Http\Controllers\Privacy\Admin\PrivacyAdminController;
use App\Http\Controllers\Privacy\PolicyController;
use App\Http\Controllers\Privacy\VerificationController;
use ArtisanPackUI\Privacy\Http\Controllers\DataExportDownloadController;
use ArtisanPackUI\Privacy\Http\Controllers\ReconsentController;
use Illuminate\Support\Facades\Route;

/*
| The privacy package's web + admin route registration is disabled in
| config/artisanpack/privacy.php because it renders Blade views. Below
| we mount the JSON API the React banners + admin components hit, plus
| the two non-view web endpoints (reconsent submit + export download),
| then layer our own Inertia pages on top of the same controllers the
| package exposes.
*/

Route::prefix('api/privacy')->middleware('web')->group(function (): void {
    require base_path('vendor/artisanpack-ui/privacy/routes/api.php');
});

Route::middleware('web')->group(function (): void {
    Route::post('/reconsent', [ReconsentController::class, 'store'])
        ->middleware('throttle:privacy-verification')
        ->name('privacy.policy.reconsent');

    Route::get('/exports/{path}', DataExportDownloadController::class)
        ->where('path', '.*')
        ->name('privacy.exports.download');

    Route::get('/policy', [PolicyController::class, 'show'])->name('privacy.policy.show');
    Route::get('/policy/{version}', [PolicyController::class, 'showVersion'])
        ->where('version', '[A-Za-z0-9._\-]+')
        ->name('privacy.policy.show-version');

    Route::get('/verify/{token}', [VerificationController::class, 'show'])
        ->name('privacy.verification.show');
    Route::post('/verify/{token}', [VerificationController::class, 'verify'])
        ->middleware('throttle:privacy-verification')
        ->name('privacy.verification.verify');
});

Route::middleware(['auth', 'verified', 'can:manage-privacy'])
    ->prefix('dashboard/privacy')
    ->name('dashboard.privacy.')
    ->group(function (): void {
        Route::get('/', [PrivacyAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/consents', [PrivacyAdminController::class, 'consents'])->name('consents');
        Route::get('/data-requests', [PrivacyAdminController::class, 'dataRequests'])->name('data-requests');
        Route::get('/compliance-report', [PrivacyAdminController::class, 'complianceReport'])->name('compliance-report');
        Route::get('/breaches', [PrivacyAdminController::class, 'breaches'])->name('breaches');
        Route::get('/breaches/report', [PrivacyAdminController::class, 'breachReport'])->name('breaches.report');
        Route::get('/breaches/{id}', [PrivacyAdminController::class, 'breachDetail'])
            ->whereNumber('id')
            ->name('breaches.show');
    });
