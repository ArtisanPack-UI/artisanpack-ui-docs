<?php

declare(strict_types=1);

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

it('exposes the metrics ingest endpoint under /api/performance/metrics', function () {
    $response = $this->postJson('/api/performance/metrics', [
        'name' => 'LCP',
        'value' => 1234.5,
        'delta' => 1234.5,
        'id' => 'v3-1234567890-1',
        'rating' => 'good',
        'navigationType' => 'navigate',
        'page' => '/',
        'route' => 'home',
        'timestamp' => now()->getTimestampMs(),
    ]);

    // The endpoint exists (not a 404) and accepts the payload — either
    // returns 2xx/204 on success or a 422 if the payload shape drifts,
    // but never 404/405 which would indicate the route is not registered.
    expect($response->status())->not->toBe(404)
        ->and($response->status())->not->toBe(405);
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
