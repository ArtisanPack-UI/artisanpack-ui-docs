<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Http\HandleInertiaRequests;
use App\Models\User;
use ArtisanPackUI\Privacy\Concerns\HasPersonalData;
use ArtisanPackUI\Privacy\Database\Factories\DataRequestFactory;
use ArtisanPackUI\Privacy\Database\Factories\PrivacyPolicyFactory;
use ArtisanPackUI\Privacy\Models\PrivacyPolicy;
use ArtisanPackUI\Privacy\Services\ReconsentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

it('shares a null reconsent policy when nothing is active', function () {
    PrivacyPolicy::query()->delete();

    $shared = app(HandleInertiaRequests::class)->share(Request::create('/'));

    expect($shared['reconsent']())->toBeNull();
});

it('surfaces the active reconsent policy for guests when one is published', function () {
    PrivacyPolicy::query()->delete();

    $policy = PrivacyPolicyFactory::new()->create([
        'version' => '2026.01',
        'regulation' => null,
        'active' => true,
        'requires_reconsent' => true,
        'published_at' => now(),
    ]);

    $shared = app(HandleInertiaRequests::class)->share(Request::create('/'));

    expect($shared['reconsent']())->toMatchArray([
        'version' => '2026.01',
        'regulation' => null,
    ])->and($shared['reconsent']()['url'])->toEndWith("/policy/{$policy->version}");
});

it('hides the reconsent prop from a user who has already consented to the active policy', function () {
    PrivacyPolicy::query()->delete();

    $policy = PrivacyPolicyFactory::new()->create([
        'version' => '2026.01',
        'regulation' => null,
        'active' => true,
        'requires_reconsent' => true,
        'published_at' => now(),
    ]);

    $user = User::factory()->create();
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    app(ReconsentService::class)->grant($policy, $user, $request);

    $shared = app(HandleInertiaRequests::class)->share($request);

    expect($shared['reconsent']())->toBeNull();
});

it('exposes the manage-privacy gate to admins only', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $editor = User::factory()->create(['role' => Role::Editor]);

    expect(Gate::forUser($admin)->allows('manage-privacy'))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('manage-privacy'))->toBeFalse();
});

it('registers HasPersonalData on the User model', function () {
    expect(class_uses_recursive(User::class))
        ->toHaveKey(HasPersonalData::class);
});

it('renders the privacy policy as an Inertia page', function () {
    PrivacyPolicy::query()->delete();

    PrivacyPolicyFactory::new()->create([
        'version' => '2026.01',
        'regulation' => null,
        'active' => true,
        'requires_reconsent' => false,
        'published_at' => now(),
    ]);

    $response = $this->get('/policy');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('Privacy/Policy')
            ->where('policy.version', '2026.01')
            ->has('policy.html'),
    );
});

it('404s the policy route when no policy has ever been published', function () {
    PrivacyPolicy::query()->delete();

    $this->get('/policy')->assertNotFound();
});

it('gates the admin dashboard behind the manage-privacy ability', function () {
    $editor = User::factory()->create(['role' => Role::Editor]);
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($editor)->get('/dashboard/privacy')->assertForbidden();
    $this->actingAs($admin)->get('/dashboard/privacy')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Privacy/Admin/Dashboard'));
});

it('mounts the privacy JSON API alongside the disabled Blade routes', function () {
    $this->get('/api/privacy/categories')->assertOk();
});

it('renders a historical policy version by version string', function () {
    PrivacyPolicy::query()->delete();

    PrivacyPolicyFactory::new()->create([
        'version' => '2025.01',
        'regulation' => null,
        'active' => false,
        'published_at' => now()->subYear(),
    ]);

    $response = $this->get('/policy/2025.01');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page->component('Privacy/Policy')->where('policy.version', '2025.01'),
    );
});

it('404s an unknown policy version', function () {
    PrivacyPolicy::query()->delete();

    $this->get('/policy/does-not-exist')->assertNotFound();
});

it('renders the verify Inertia page for a valid token', function () {
    $dataRequest = DataRequestFactory::new()->create([
        'verification_token' => 'test-token-123',
    ]);

    $response = $this->get('/verify/test-token-123');

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('Privacy/Verify')
            ->where('token', 'test-token-123')
            ->where('data_request.id', $dataRequest->id)
            ->where('expired', false),
    );
});

it('404s the verify page for an unknown token', function () {
    $this->get('/verify/nope')->assertNotFound();
});

it('confirms the request and redirects back to the verify page on POST', function () {
    $dataRequest = DataRequestFactory::new()->create([
        'verification_token' => 'confirm-me',
    ]);

    $response = $this->post('/verify/confirm-me');

    $response->assertRedirect('/verify/confirm-me');
    $response->assertSessionHas('status');
    expect($dataRequest->fresh()->status)->not->toBe('pending');
});

it('requires auth to POST /reconsent', function () {
    $this->postJson('/reconsent', ['version' => '1.0.0'])->assertStatus(401);
});

it('records reconsent for the authenticated user against the active policy', function () {
    PrivacyPolicy::query()->delete();

    $policy = PrivacyPolicyFactory::new()->create([
        'version' => '2027.01',
        'regulation' => null,
        'active' => true,
        'requires_reconsent' => true,
        'published_at' => now(),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/reconsent', ['version' => $policy->version])
        ->assertOk()
        ->assertJson(['ok' => true, 'version' => $policy->version]);

    expect(app(ReconsentService::class)->isUpToDate($user))->toBeTrue();
});

it('gates every admin sub-route behind manage-privacy', function () {
    $editor = User::factory()->create(['role' => Role::Editor]);

    $paths = [
        '/dashboard/privacy',
        '/dashboard/privacy/consents',
        '/dashboard/privacy/data-requests',
        '/dashboard/privacy/compliance-report',
        '/dashboard/privacy/breaches',
        '/dashboard/privacy/breaches/report',
        '/dashboard/privacy/breaches/42',
    ];

    foreach ($paths as $path) {
        $this->actingAs($editor)->get($path)->assertForbidden();
    }
});

it('renders every admin Inertia page for admins', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $expected = [
        '/dashboard/privacy/consents' => 'Privacy/Admin/Consents',
        '/dashboard/privacy/data-requests' => 'Privacy/Admin/DataRequests',
        '/dashboard/privacy/compliance-report' => 'Privacy/Admin/ComplianceReport',
        '/dashboard/privacy/breaches' => 'Privacy/Admin/Breaches',
        '/dashboard/privacy/breaches/report' => 'Privacy/Admin/BreachReport',
        '/dashboard/privacy/breaches/42' => 'Privacy/Admin/BreachDetail',
    ];

    foreach ($expected as $path => $component) {
        $this->actingAs($admin)
            ->get($path)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component($component));
    }
});

it('schedules the daily privacy maintenance commands', function () {
    $bootstrap = (string) file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)
        ->toContain('withSchedule')
        ->toContain('privacy:purge-expired')
        ->toContain('privacy:process-requests');
});
