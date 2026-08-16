<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use ArtisanPackUI\Analytics\Facades\Analytics;
use ArtisanPackUI\Analytics\Models\Event;
use ArtisanPackUI\Analytics\Models\PageView;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

it('drives the analytics dashboard through the Inertia driver behind the admin gate', function () {
    expect(config('artisanpack.analytics.dashboard_driver'))->toBe('inertia')
        ->and(config('artisanpack.analytics.dashboard_route'))->toBe('dashboard/analytics')
        ->and(config('artisanpack.analytics.dashboard_middleware'))
        ->toContain('auth')
        ->toContain('verified')
        ->toContain('can:view-analytics');
});

it('processes analytics jobs synchronously by default so beacons persist without a queue worker', function () {
    // The vendor default is async dispatch onto the `analytics` queue.
    // Nothing in `composer run dev` listens on that queue, so keeping
    // async on would silently drop every pageview into Redis and the
    // dashboard would stay empty — the exact bug we hit in setup.
    // Flip this back to true only after wiring a Horizon supervisor
    // (or equivalent) that watches the queue named below.
    expect(config('artisanpack.analytics.local.queue_processing'))->toBeFalse()
        ->and(config('artisanpack.analytics.local.queue_name'))->toBe('analytics');
});

it('persists a page-view row synchronously when the beacon reaches the ingest endpoint', function () {
    // End-to-end guard against re-introducing async dispatch. The
    // controller returns 204 either way; the only trustworthy signal
    // is a row in `analytics_page_views` on the same request.
    $before = PageView::query()->count();

    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
    ])->postJson('/api/analytics/pageview', [
        'visitor_id' => 'test-visitor-'.uniqid(),
        'session_id' => '11111111-2222-4333-8444-555555555555',
        'path' => '/docs/getting-started',
        'title' => 'Getting Started',
    ]);

    $response->assertNoContent();

    expect(PageView::query()->count())->toBe($before + 1);
});

it('renders analytics-track=false into the app layout for authenticated users', function () {
    // The client `analytics.ts` skips loading the vendor tracker when
    // this meta tag reads "false", so the assertion here is the
    // contract the JS half of the auth-skip relies on.
    $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('name="analytics-track" content="false"', false);
});

it('renders analytics-track=true into the app layout for guest visitors', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('name="analytics-track" content="true"', false);
});

it('coerces integer screen/viewport dimensions so real browser beacons do not TypeError', function () {
    // The vendor `PageViewData` DTO types `$screenWidth` etc. as
    // `?string` under `declare(strict_types=1)`, but the client
    // tracker sends `window.screen.width` (an integer). Without the
    // app's `CoerceAnalyticsBeaconTypes` middleware the resulting
    // TypeError gets caught, logged, and swallowed as a silent 204,
    // and every real browser pageview is lost.
    $before = PageView::query()->count();

    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
    ])->postJson('/api/analytics/pageview', [
        'visitor_id' => 'browser-visitor-'.uniqid(),
        'session_id' => '22222222-3333-4444-8555-666666666666',
        'path' => '/docs',
        'title' => 'Docs',
        // Integers, exactly as the tracker JS emits them.
        'screen_width' => 2560,
        'screen_height' => 1440,
        'viewport_width' => 1920,
        'viewport_height' => 1080,
    ]);

    $response->assertNoContent();

    expect(PageView::query()->count())
        ->toBe($before + 1, 'Integer screen/viewport dimensions should not silently drop the beacon');
});

it('keeps the local provider active by default and leaves external providers opt-in', function () {
    expect(config('artisanpack.analytics.default'))->toBe('local')
        ->and(config('artisanpack.analytics.active_providers'))->toContain('local')
        ->and(config('artisanpack.analytics.providers.google.enabled'))->toBeFalse()
        ->and(config('artisanpack.analytics.providers.plausible.enabled'))->toBeFalse();
});

it('tracks SPA navigation through exactly one tracker so views are neither lost nor doubled', function () {
    // SPA route changes must reach analytics_page_views exactly once. Two
    // trackers can do it and they must not both be live:
    //
    //   1. the vendor tracker's History-API wrapper (default on in 1.5), and
    //   2. this app's guarded `inertia:navigate` bridge.
    //
    // The app runs the bridge and disables the wrapper. The bridge skips
    // sensitive auth paths client-side; the wrapper has no such exclusion and
    // would POST token-bearing URLs to the network. Enabling both would double
    // count every navigation. Pin both halves of that invariant.
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

    expect($composer['require']['artisanpack-ui/analytics'])->toBe('^1.5');

    $client = (string) file_get_contents(base_path('resources/js/lib/analytics.ts'));

    // The vendor History-API tracker must stay off, or it double counts with
    // the bridge and leaks sensitive paths; the guarded bridge must stay, or
    // SPA page views go untracked.
    expect($client)
        ->toContain('trackHistoryChanges: false')
        ->toContain("addEventListener('inertia:navigate'");
});

it('excludes every client-side sensitive path server-side as well', function () {
    // The client keeps these URLs off the network two ways: the boot gate
    // skips the entry URL, and the guarded `inertia:navigate` bridge skips SPA
    // navigations into them. `excluded_paths` is the server-side belt behind
    // both — anything that still reaches `/api/analytics/*` stays out of
    // `analytics_page_views`. Every pattern in the client's
    // SENSITIVE_PATH_PATTERNS must have server-side cover.
    $excluded = config('artisanpack.analytics.privacy.excluded_paths');

    expect($excluded)
        ->toContain('/reset-password/*')
        ->toContain('/forgot-password')
        ->toContain('/verify/*')
        ->toContain('/email/verify/*')
        ->toContain('/two-factor-challenge')
        ->toContain('/user/confirm-password');
});

it('excludes admin and single-use privacy tokens from server-side tracking', function () {
    // The excluded_paths list is what the analytics ingest middleware
    // consults before writing to analytics_page_views. Auth-flow URLs
    // carry secret tokens that must never land in the retention window.
    $excludes = config('artisanpack.analytics.privacy.excluded_paths');

    expect($excludes)
        ->toContain('/dashboard/*')
        ->toContain('/api/*')
        ->toContain('/verify/*')
        ->toContain('/reset-password/*')
        ->toContain('/email/verify/*')
        ->toContain('/two-factor-challenge')
        ->toContain('/logout');
});

it('requires the privacy consent gate before the tracker fires', function () {
    // The client tracker is loaded from resources/js/lib/analytics.ts only
    // after PrivacyConsent.whenConsented('analytics') resolves. Belt and
    // braces: also assert the server-side consent gate is on so ingest
    // POSTs from an untrusted origin fail closed.
    expect(config('artisanpack.analytics.privacy.consent_required'))->toBeTrue()
        ->and(config('artisanpack.analytics.privacy.respect_dnt'))->toBeTrue();
});

it('registers the six Inertia analytics dashboard routes behind auth+verified+admin', function () {
    $routes = collect(Route::getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'dashboard/analytics'))
        ->filter(fn ($route) => $route->uri() !== 'dashboard/analytics/realtime/feed')
        ->mapWithKeys(fn ($route) => [$route->uri() => $route]);

    expect($routes)->toHaveKeys([
        'dashboard/analytics',
        'dashboard/analytics/pages',
        'dashboard/analytics/traffic',
        'dashboard/analytics/audience',
        'dashboard/analytics/events',
        'dashboard/analytics/realtime',
    ]);

    foreach ($routes as $route) {
        expect($route->gatherMiddleware())
            ->toContain('auth')
            ->toContain('verified')
            ->toContain('can:view-analytics');
    }
});

it('rejects a verified non-admin who deep-links to any analytics dashboard route', function () {
    // The admin filter that hides Analytics in `AdminLayout.tsx` is
    // just a UI hint — a verified editor could still type the URL.
    // The `can:view-analytics` gate on `dashboard_middleware` and on
    // the app-side realtime routes is the actual defence.
    $editor = User::factory()->create(['role' => Role::Editor, 'email_verified_at' => now()]);

    foreach ([
        '/dashboard/analytics',
        '/dashboard/analytics/pages',
        '/dashboard/analytics/traffic',
        '/dashboard/analytics/audience',
        '/dashboard/analytics/events',
        '/dashboard/analytics/realtime',
        '/dashboard/analytics/realtime/feed',
        '/dashboard/integrations/google',
    ] as $path) {
        $this->actingAs($editor)->get($path)->assertForbidden();
    }
});

it('routes unauthenticated dashboard visits to the login page', function () {
    $this->get('/dashboard/analytics')->assertRedirect('/login');
});

it('renders the analytics dashboard Inertia component for an admin', function () {
    $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

    $response = $this->actingAs($admin)->get('/dashboard/analytics');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('Analytics/Dashboard')
            ->has('stats')
            ->has('chartData')
            ->has('topPages')
            ->has('trafficSources')
            ->has('dateRange')
            ->where('dateRangePreset', '30d'),
    );
});

it('renders the events dashboard page with the event breakdown prop', function () {
    $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

    $response = $this->actingAs($admin)->get('/dashboard/analytics/events');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('Analytics/Events')
            ->has('eventBreakdown'),
    );
});

it('shadows the vendor realtime dashboard route with the app controller so recent_pageviews render', function () {
    // The vendor's `AnalyticsQuery::getRealtime()` only returns
    // `active_visitors` and `timestamp` — no recent-hits list. Our
    // `App\Http\Controllers\Analytics\RealtimeController` supplements
    // it by querying `analytics_page_views` directly and passing a
    // `recent_pageviews` prop so the page renders live activity.
    $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

    // Route the fixture through the actual ingest pipeline so the
    // vendor's Visitor / Session / PageView records satisfy every FK
    // and NOT NULL constraint the way a real browser beacon would.
    $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
    ])->postJson('/api/analytics/pageview', [
        'visitor_id' => 'realtime-fixture-visitor',
        'session_id' => 'aa000000-0000-4000-8000-000000000001',
        'path' => '/docs/realtime-fixture',
        'title' => 'Realtime Fixture',
    ])->assertNoContent();

    $response = $this->actingAs($admin)->get('/dashboard/analytics/realtime');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('Analytics/Realtime')
            ->has('realtime.recent_pageviews', 1)
            ->where('realtime.recent_pageviews.0.path', '/docs/realtime-fixture')
            ->where('realtime.recent_pageviews.0.title', 'Realtime Fixture')
            ->has('realtime.window_minutes')
            ->has('realtime.active_visitors'),
    );

    $feed = $this->actingAs($admin)->getJson('/dashboard/analytics/realtime/feed');

    $feed->assertOk();
    $feed->assertJsonPath('recent_pageviews.0.path', '/docs/realtime-fixture');
    $feed->assertJsonStructure([
        'active_visitors',
        'recent_pageviews' => [['path', 'title', 'timestamp', 'visitor_id']],
        'window_minutes',
        'timestamp',
    ]);
});

it('renders the Google integration page with credential status props', function () {
    $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

    $response = $this->actingAs($admin)->get('/dashboard/integrations/google');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('Integrations/Google')
            ->has('propertyId')
            ->has('hasCredentials'),
    );
});

it('records events dispatched via the Analytics facade to the local store', function () {
    Analytics::event(
        name: 'docs.code_copy',
        properties: ['content_key' => 'core/getting-started', 'length' => 42],
        category: 'docs',
    );

    expect(
        Event::query()
            ->where('name', 'docs.code_copy')
            ->exists(),
    )->toBeTrue();
});

it('exposes the google OAuth routes the ConnectionManager component talks to', function () {
    $routes = collect(Route::getRoutes())
        ->map(fn ($r) => $r->uri())
        ->all();

    expect($routes)
        ->toContain('google/auth/connect')
        ->toContain('google/auth/callback')
        ->toContain('google/auth/status')
        ->toContain('google/auth/disconnect');
});

it('exposes the analytics-google reporting routes behind auth', function () {
    $routes = collect(Route::getRoutes())
        ->filter(fn ($r) => str_starts_with($r->uri(), 'analytics-google'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect($route->gatherMiddleware())
            ->toContain('web')
            ->toContain('auth');
    }
});

it('registers analytics-google as the GA4 provider name', function () {
    expect(config('analytics-google.provider_name'))->toBe('google-ga4');
});

it('exposes the server-side Measurement Protocol forwarding config from analytics-google 1.1', function () {
    // analytics-google 1.1 made the google-ga4 provider actually relay page
    // views and events to GA4 over the Measurement Protocol; before, it was a
    // silent no-op. The relay reads these keys under `tracking`. Our published
    // config file must carry them: under `config:cache` the package's
    // mergeConfigFrom is skipped, so a missing key resolves to null and
    // forwarding silently stays off — the exact failure this release fixes.
    $tracking = config('analytics-google.tracking');

    expect($tracking)
        ->toHaveKeys(['api_secret', 'server_side', 'page_location_base', 'timeout', 'debug'])
        ->and(config('analytics-google.tracking.server_side'))->toBeTrue()
        ->and(config('analytics-google.tracking.timeout'))->toBe(3);
});

it('schedules the daily analytics:cleanup command', function () {
    $bootstrap = (string) file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)
        ->toContain('analytics:cleanup')
        ->toContain("config('artisanpack.analytics.retention.cleanup_schedule'");
});

it('created all analytics and google database tables via migration', function () {
    $tables = [
        'analytics_sites',
        'analytics_visitors',
        'analytics_sessions',
        'analytics_page_views',
        'analytics_events',
        'analytics_consents',
        'analytics_aggregates',
        'google_connections',
        'google_configurations',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))
            ->toBeTrue("Expected `{$table}` table to exist after running migrations");
    }
});
