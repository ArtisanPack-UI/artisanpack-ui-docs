<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Modules\Packages\Package;

beforeEach(function () {
    RateLimiter::clear('api');
    config()->set('cors.paths', ['api/*', 'sanctum/csrf-cookie']);
    config()->set('cors.allowed_methods', ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS']);
    config()->set('cors.allowed_origins', ['https://artisanpackui.dev']);
    config()->set('cors.allowed_origins_patterns', []);
    config()->set('cors.allowed_headers', ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With']);
    config()->set('cors.supports_credentials', false);
});

test('CORS preflight from a configured origin is allowed', function () {
    $response = $this->call(
        'OPTIONS',
        '/api/v1/packages',
        server: [
            'HTTP_ORIGIN' => 'https://artisanpackui.dev',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ],
    );

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('https://artisanpackui.dev');
});

test('CORS preflight from an unlisted origin is rejected', function () {
    $response = $this->call(
        'OPTIONS',
        '/api/v1/packages',
        server: [
            'HTTP_ORIGIN' => 'https://evil.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ],
    );

    // The security-critical property: the ACAO header must not echo
    // the evil origin and must not be `*`. Either would let the
    // browser hand the response over. (Symfony's CORS middleware
    // may fall back to the first configured origin here, which the
    // browser rejects because it doesn't match the request Origin.)
    $acao = $response->headers->get('Access-Control-Allow-Origin');
    expect($acao)->not->toBe('https://evil.example.com');
    expect($acao)->not->toBe('*');
});

test('CORS response does not enable credentials', function () {
    $response = $this->call(
        'OPTIONS',
        '/api/v1/packages',
        server: [
            'HTTP_ORIGIN' => 'https://artisanpackui.dev',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ],
    );

    expect($response->headers->get('Access-Control-Allow-Credentials'))->toBeNull();
});

test('api throttle isolates buckets per Sanctum token', function () {
    config()->set('artisanpack.api.rate_limit_per_minute', 2);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // Burn userA's bucket to zero.
    Sanctum::actingAs($userA, ['packages:read']);
    $this->getJson(route('api.v1.packages.index'))->assertOk();
    $this->getJson(route('api.v1.packages.index'))->assertOk();
    $this->getJson(route('api.v1.packages.index'))->assertStatus(429);

    // userB — different token — must still be allowed through. If the
    // limiter silently collapses to IP, this hits 429 too.
    Sanctum::actingAs($userB, ['packages:read']);
    $this->getJson(route('api.v1.packages.index'))->assertOk();
});

test('CORS config declares no wildcard origins', function () {
    expect(config('cors.allowed_origins'))->not->toContain('*');
});

test('api routes reject requests once the api throttle limit is exceeded', function () {
    config()->set('artisanpack.api.rate_limit_per_minute', 3);

    $user = User::factory()->create();
    Sanctum::actingAs($user, ['packages:read']);
    Package::factory()->count(1)->create();

    for ($i = 0; $i < 3; $i++) {
        $this->getJson(route('api.v1.packages.index'))->assertOk();
    }

    $this->getJson(route('api.v1.packages.index'))->assertStatus(429);
});
