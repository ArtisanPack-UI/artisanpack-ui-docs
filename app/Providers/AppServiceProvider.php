<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\FileViewFinder;
use Modules\Core\Policies\SettingPolicy;
use Modules\Core\Setting;
use Modules\Packages\Package;
use Modules\Packages\Policies\PackagePolicy;
use Modules\Users\Policies\UserPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
        Gate::policy(Setting::class, SettingPolicy::class);

        $this->registerInertiaModulePageNamespaces();
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
