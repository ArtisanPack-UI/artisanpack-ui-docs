<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->instance(
            VerifyEmailResponse::class,
            new class implements VerifyEmailResponse
            {
                public function toResponse($request)
                {
                    return $request->wantsJson()
                        ? new JsonResponse('', 204)
                        : redirect()->intended(route('verification.verified'));
                }
            }
        );

        $this->app->instance(
            LoginResponse::class,
            new class implements LoginResponse
            {
                public function toResponse($request)
                {
                    if ($request->wantsJson()) {
                        return new JsonResponse(['two_factor' => false]);
                    }

                    $target = redirect()->intended(Fortify::redirects('login'))->getTargetUrl();

                    return $request->header('X-Inertia')
                        ? Inertia::location($target)
                        : redirect()->to($target);
                }
            }
        );

        $this->app->instance(
            TwoFactorLoginResponse::class,
            new class implements TwoFactorLoginResponse
            {
                public function toResponse($request)
                {
                    if ($request->wantsJson()) {
                        return new JsonResponse('', 204);
                    }

                    $target = redirect()->intended(Fortify::redirects('login'))->getTargetUrl();

                    return $request->header('X-Inertia')
                        ? Inertia::location($target)
                        : redirect()->to($target);
                }
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id') ?: $request->ip());
        });
    }
}
