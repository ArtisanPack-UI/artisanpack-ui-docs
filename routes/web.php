<?php

use App\Http\Controllers\Analytics\RealtimeController as AnalyticsRealtimeController;
use App\Http\Controllers\Analytics\SourceController as AnalyticsSourceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Integrations\GoogleController as GoogleIntegrationController;
use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Admin-only surfaces. `can:view-analytics` matches the sidebar filter
// in `AdminLayout.tsx` so a verified editor cannot deep-link to the
// analytics dashboard or the recent-pageviews feed (which surfaces
// visitor IDs). The vendor Inertia dashboard routes at
// `dashboard/analytics/{pages,traffic,audience,events}` get the same
// gate via `dashboard_middleware` in `config/artisanpack/analytics.php`.
Route::middleware(['auth', 'verified', 'can:view-analytics'])->group(function () {
    Route::get('dashboard/integrations/google', [GoogleIntegrationController::class, 'show'])
        ->name('dashboard.integrations.google');

    // Shadows the vendor `analytics.dashboard.realtime` route so the
    // page can render a "recent pageviews" list — the upstream
    // controller only ships `active_visitors` + `timestamp`. Because
    // `routes/web.php` loads before the analytics service provider
    // registers its own dashboard routes, this definition wins.
    Route::get('dashboard/analytics/realtime', [AnalyticsRealtimeController::class, 'show'])
        ->name('dashboard.analytics.realtime');

    // JSON companion for the Analytics/Realtime page's client poll.
    // Same window/limit shape as `show()` so `recent_pageviews` stays
    // interchangeable between the initial Inertia render and each
    // 10-second refresh.
    Route::get('dashboard/analytics/realtime/feed', [AnalyticsRealtimeController::class, 'feed'])
        ->name('dashboard.analytics.realtime.feed');

    // Persists the admin's choice between the local first-party analytics
    // store and Google Analytics for every /dashboard/analytics/* page.
    Route::post('dashboard/analytics/source', [AnalyticsSourceController::class, 'update'])
        ->name('dashboard.analytics.source.update');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'dashboard/settings/profile');
    Route::redirect('settings/profile', 'dashboard/settings/profile');
    Route::redirect('settings/password', 'dashboard/settings/password');
    Route::redirect('settings/appearance', 'dashboard/settings/appearance');

    Route::prefix('dashboard/settings')->name('dashboard.settings.')->group(function () {
        Route::get('profile', [ProfileController::class, 'show'])->name('profile');
        Route::get('password', [PasswordController::class, 'show'])->name('password');
        Route::get('appearance', [AppearanceController::class, 'show'])->name('appearance');
        Route::patch('appearance', [AppearanceController::class, 'update'])->name('appearance.update');
        Route::get('two-factor', [TwoFactorController::class, 'show'])->name('two-factor');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/privacy.php';
