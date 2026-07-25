<?php

declare(strict_types=1);

use App\Analytics\AnalyticsSourceResolver;
use App\Analytics\Ga4QueryProvider;
use ArtisanPackUI\Analytics\Data\DateRange;
use ArtisanPackUI\AnalyticsGoogle\Reporting\Ga4DataClient;
use ArtisanPackUI\AnalyticsGoogle\Reporting\ReportResponse;
use ArtisanPackUI\Google\Models\GoogleConnection;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Carbon;

function ga4Provider(?ReportResponse $response, bool $connected = true): Ga4QueryProvider
{
    $client = Mockery::mock(Ga4DataClient::class);
    $client->shouldReceive('isAvailable')->andReturn(true);

    if ($response !== null) {
        $client->shouldReceive('runReport')->andReturn($response);
    }

    $resolver = Mockery::mock(AnalyticsSourceResolver::class);
    $resolver->shouldReceive('googleConnection')->andReturn(
        $connected ? new GoogleConnection : null,
    );

    return new Ga4QueryProvider(
        $client,
        $resolver,
        app('config'),
        app(HttpFactory::class),
    );
}

function ga4Range(): DateRange
{
    return new DateRange(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-30'),
    );
}

it('returns zero for total metrics when no google connection is available', function () {
    $provider = ga4Provider(response: null, connected: false);

    expect($provider->getPageViews(ga4Range()))->toBe(0)
        ->and($provider->getVisitors(ga4Range()))->toBe(0)
        ->and($provider->getSessions(ga4Range()))->toBe(0);
});

it('sums total metrics across every returned row', function () {
    $response = new ReportResponse([
        'metricHeaders' => [['name' => 'screenPageViews']],
        'rows' => [
            ['metricValues' => [['value' => '120']]],
            ['metricValues' => [['value' => '80']]],
        ],
    ]);

    expect(ga4Provider($response)->getPageViews(ga4Range()))->toBe(200);
});

it('parses top pages into the local shape', function () {
    $response = new ReportResponse([
        'dimensionHeaders' => [['name' => 'pagePath'], ['name' => 'pageTitle']],
        'metricHeaders' => [['name' => 'screenPageViews'], ['name' => 'totalUsers']],
        'rows' => [
            [
                'dimensionValues' => [['value' => '/docs/getting-started'], ['value' => 'Getting Started']],
                'metricValues' => [['value' => '340'], ['value' => '210']],
            ],
            [
                'dimensionValues' => [['value' => '/pricing'], ['value' => 'Pricing']],
                'metricValues' => [['value' => '120'], ['value' => '95']],
            ],
        ],
    ]);

    $rows = ga4Provider($response)->getTopPages(ga4Range())->all();

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toMatchArray([
            'path' => '/docs/getting-started',
            'title' => 'Getting Started',
            'views' => 340,
            'unique_views' => 210,
        ]);
});

it('normalises GA4 YYYYMMDD dates on the daily time series', function () {
    $response = new ReportResponse([
        'dimensionHeaders' => [['name' => 'date']],
        'metricHeaders' => [['name' => 'screenPageViews'], ['name' => 'totalUsers']],
        'rows' => [
            [
                'dimensionValues' => [['value' => '20260601']],
                'metricValues' => [['value' => '10'], ['value' => '7']],
            ],
        ],
    ]);

    $rows = ga4Provider($response)->getPageViewsOverTime(ga4Range())->all();

    expect($rows[0]['date'])->toBe('2026-06-01');
});

it('computes percentage shares on the browser breakdown', function () {
    $response = new ReportResponse([
        'dimensionHeaders' => [['name' => 'browser'], ['name' => 'browserVersion']],
        'metricHeaders' => [['name' => 'sessions']],
        'rows' => [
            [
                'dimensionValues' => [['value' => 'Chrome'], ['value' => '128']],
                'metricValues' => [['value' => '75']],
            ],
            [
                'dimensionValues' => [['value' => 'Safari'], ['value' => '17']],
                'metricValues' => [['value' => '25']],
            ],
        ],
    ]);

    $rows = ga4Provider($response)->getBrowserBreakdown(ga4Range())->all();

    expect($rows[0]['percentage'])->toBe(75.0)
        ->and($rows[1]['percentage'])->toBe(25.0);
});
