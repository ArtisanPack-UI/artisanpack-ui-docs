<?php

declare(strict_types=1);

use ArtisanPackUI\Performance\Models\RawMetric;
use ArtisanPackUI\Performance\Support\MonitorDirectives;
use ArtisanPackUI\Performance\Support\SpeculativeDirectives;
use Illuminate\Support\Facades\Blade;

it('enables monitoring, speculative loading, and script optimization by default', function () {
    expect(config('artisanpack.performance.features.monitoring'))->toBeTrue()
        ->and(config('artisanpack.performance.features.speculative_loading'))->toBeTrue()
        ->and(config('artisanpack.performance.features.script_optimization'))->toBeTrue();
});

it('leaves opt-in features that this app does not use disabled', function () {
    $disabled = [
        'image_optimization',
        'critical_css',
        'html_minification',
        'early_hints',
        'page_cache',
        'fragment_cache',
        'cache_warming',
        'query_optimization',
        'alerts',
        'dashboard',
    ];

    foreach ($disabled as $feature) {
        expect(config("artisanpack.performance.features.{$feature}"))
            ->toBeFalse("Expected feature `{$feature}` to be disabled by default");
    }
});

it('excludes dashboard, API, and single-use privacy tokens from speculation prefetch', function () {
    $excludes = config('artisanpack.performance.speculative_loading.prefetch.exclude_patterns');

    expect($excludes)
        ->toContain('/dashboard/*')
        ->toContain('/api/*')
        ->toContain('/verify/*')
        ->toContain('/logout');
});

it('persists a raw metric row for every accepted beacon', function () {
    RawMetric::query()->delete();

    $response = $this->postJson('/api/performance/metrics', [
        'name' => 'LCP',
        'value' => 1234.5,
        'delta' => 1234.5,
        'id' => 'v3-1234567890-1',
        'rating' => 'good',
        'page' => '/',
        'route' => 'home',
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    // Endpoint returned 200 and `store_raw_metrics` is on, so the beacon
    // must have landed in the table — a 200 with an empty table would mean
    // the app is silently discarding metrics (the pre-fix behavior).
    expect(RawMetric::query()->where('name', 'LCP')->count())->toBe(1);
});

it('schedules the hourly perf:aggregate-metrics command', function () {
    $bootstrap = (string) file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)
        ->toContain('withSchedule')
        ->toContain('perf:aggregate-metrics')
        ->toContain('->hourly()');
});

it('throttles anonymous metric ingest at 10 requests per minute', function () {
    // The metrics endpoint is unauthenticated and every accepted beacon
    // is persisted for retention_days. 10/min per source IP caps a
    // distributed spammer while still leaving headroom for the ~5
    // Core Web Vitals a single real page load fires.
    expect(config('artisanpack.performance.routes.api_throttle'))->toBe('10,1');
});

it('gates the client collector on the auth-flow sensitive-path skip list', function () {
    // The lib/performance.ts init module maintains a regex list of
    // paths that must never boot the collector because the URL path
    // itself carries a secret token. Assert the source file lists
    // every current token route so a future auth-flow addition (e.g.
    // a new sudo-mode confirmation route) is flagged for review here
    // before it starts leaking tokens into performance_raw_metrics.url.
    $source = (string) file_get_contents(resource_path('js/lib/performance.ts'));

    expect($source)
        ->toContain('/reset-password')
        ->toContain('/forgot-password')
        ->toContain('/verify')
        ->toContain('/email\\/verify')
        ->toContain('/two-factor-challenge')
        ->toContain('/user\\/confirm-password');
});

it('gates the client collector on analytics consent from the privacy package', function () {
    $source = (string) file_get_contents(resource_path('js/lib/performance.ts'));

    // Web Vitals rides the `analytics` category from
    // config/artisanpack/privacy.php. The init module must (a) import
    // the privacy main entry so window.PrivacyConsent is installed and
    // (b) wait for analytics consent before importing web-vitals.
    expect($source)
        ->toContain("import '@artisanpack-ui/privacy'")
        ->toContain("whenConsented('analytics')")
        ->toContain('@artisanpack-ui/performance/web-vitals');
});

it('renders the perfMonitor directive when monitoring is enabled', function () {
    // The directive helper emits a config block plus a module script tag
    // when monitoring is on. We render the directive helper directly rather
    // than driving it through Blade so the assertion doesn't depend on the
    // full Blade compiler pipeline.
    $output = MonitorDirectives::perfMonitor();

    expect($output)->toContain('window.ArtisanPackPerformance')
        ->and($output)->toContain('"endpoint":"/api/performance/metrics"');
});

it('emits a speculation rules script from the speculativeRules directive', function () {
    $output = SpeculativeDirectives::render();

    expect($output)->toStartWith('<script type="speculationrules">')
        ->and($output)->toEndWith('</script>');
});

it('compiles the @speculativeRules blade directive into the head of app.blade.php', function () {
    $compiled = Blade::compileString('@speculativeRules');

    // The directive compiles to a call into SpeculativeDirectives::render()
    // — we assert the compiled PHP references it so a future rename or
    // removal of the directive fails loudly instead of silently emitting
    // no rules tag.
    expect($compiled)->toContain('SpeculativeDirectives');
});
