<?php

use App\Http\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Trust the configured reverse-proxy layer so `$request->ip()`
        // returns the real client IP (used as the unauth key for the
        // `api` rate limiter — V2_REFACTOR_PLAN.md §8.2, §9.6 #43).
        // Without this, every request collapses to the LB IP and the
        // API becomes trivially DoSable. Set `TRUSTED_PROXIES` to `*`
        // only when the LB is the sole ingress; otherwise supply an
        // explicit comma-separated CIDR list.
        // env() is used directly here because the config repository
        // is not bound yet when the middleware builder runs.
        $proxies = trim((string) env('TRUSTED_PROXIES', ''));
        if ($proxies !== '') {
            $middleware->trustProxies(
                at: $proxies === '*'
                    ? '*'
                    : array_values(array_filter(array_map('trim', explode(',', $proxies)))),
            );
        }

        // Rate-limit every request in the remote-admin API surface
        // (V2_REFACTOR_PLAN.md §8.2, §9.6 #43). The `api` limiter is
        // defined in AppServiceProvider::boot().
        $middleware->api(prepend: [
            'throttle:api',
        ]);

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('privacy:purge-expired')->daily();
        $schedule->command('privacy:process-requests')->daily();
        $schedule->command('perf:aggregate-metrics')->hourly();
        // Analytics package: prune raw events past retention_days and
        // roll up daily aggregates before deletion. Cron expression
        // sourced from ANALYTICS_CLEANUP_SCHEDULE so ops can slide the
        // window without touching this file. Bot-analysis and digest
        // emails are self-registered by the package.
        $schedule->command('analytics:cleanup')
            ->cron((string) config('artisanpack.analytics.retention.cleanup_schedule', '0 3 * * *'))
            ->withoutOverlapping()
            ->onOneServer();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            $status = $response->getStatusCode();
            $debug = app()->hasDebugModeEnabled();

            $component = match (true) {
                $status === 404 => 'Errors/NotFound',
                ($status === 500 || $status === 503) && ! $debug => 'Errors/ServerError',
                default => null,
            };

            if ($component === null) {
                return $response;
            }

            return Inertia::render($component, ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
