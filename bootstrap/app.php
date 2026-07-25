<?php

use App\Http\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
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
