<?php

namespace App\Providers;

use App\Analytics\DashboardDataSourceManager;
use App\Http\Middleware\CoerceAnalyticsBeaconTypes;
use App\Models\User;
use ArtisanPackUI\Analytics\Services\AnalyticsQuery;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\FileViewFinder;
use Modules\Core\Policies\SettingPolicy;
use Modules\Core\Setting;
use Modules\Packages\Changelog;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Packages\Policies\ChangelogPolicy;
use Modules\Packages\Policies\DocumentationPolicy;
use Modules\Packages\Policies\PackagePolicy;
use Modules\Users\Policies\UserPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swap the vendor AnalyticsQuery service for our source-switching
        // subclass so the vendor InertiaDashboardController transparently
        // dispatches to either the local provider or GA4 based on the
        // active `analytics_dashboard_source` setting.
        $this->app->bind(AnalyticsQuery::class, DashboardDataSourceManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(12)->mixedCase()->numbers()->symbols();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(Documentation::class, DocumentationPolicy::class);
        Gate::policy(Changelog::class, ChangelogPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);

        Gate::define('manage-privacy', fn (User $user): bool => $user->isAdmin());

        // Same admin-only rule the AdminLayout sidebar already applies
        // client-side to the Analytics + Integrations nav items. Any
        // route that hangs off `/dashboard/analytics/*` (vendor Inertia
        // routes + our own shadow / feed routes) or
        // `/dashboard/integrations/*` should sit behind this gate so
        // a verified-but-non-admin user cannot enumerate visitor IDs
        // via a direct URL fetch.
        Gate::define('view-analytics', fn (User $user): bool => $user->isAdmin());

        $this->registerInertiaModulePageNamespaces();
        $this->registerAnalyticsBeaconCoercion();
    }

    /**
     * Prepend `CoerceAnalyticsBeaconTypes` to the vendor `analytics`
     * middleware group so every incoming beacon has its integer
     * width/height fields stringified before validation and DTO
     * construction. See the middleware class docblock for why.
     *
     * Registered here (rather than as a route middleware alias in
     * `bootstrap/app.php`) because the analytics group is set up
     * inside `ArtisanPackUI\Analytics\AnalyticsServiceProvider::boot()`
     * and we need to push into it after the vendor's provider has
     * registered.
     */
    protected function registerAnalyticsBeaconCoercion(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->prependMiddlewareToGroup('analytics', CoerceAnalyticsBeaconTypes::class);
    }

    /**
     * Teach both Inertia view finders (runtime + testing) to resolve
     * module-scoped page names like `Core::Home` to
     * `Modules/Core/resources/js/pages/Home.tsx`, matching the JS
     * resolver in `resources/js/app.tsx`.
     */
    protected function registerInertiaModulePageNamespaces(): void
    {
        $modulesPath = base_path('Modules');

        if (! is_dir($modulesPath)) {
            return;
        }

        $moduleDirs = glob($modulesPath.'/*', GLOB_ONLYDIR) ?: [];

        $namespaces = [];
        foreach ($moduleDirs as $moduleDir) {
            $pagesPath = $moduleDir.'/resources/js/pages';
            if (is_dir($pagesPath)) {
                $namespaces[basename($moduleDir)] = $pagesPath;
            }
        }

        if ($namespaces === []) {
            return;
        }

        foreach (['inertia.view-finder', 'inertia.testing.view-finder'] as $binding) {
            $this->app->extend($binding, function (FileViewFinder $finder) use ($namespaces) {
                foreach ($namespaces as $namespace => $path) {
                    $finder->addNamespace($namespace, $path);
                }

                return $finder;
            });
        }
    }
}
