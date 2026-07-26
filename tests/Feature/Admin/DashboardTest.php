<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('renders the admin dashboard for verified users', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/Index')
            ->has('totals')
            ->has('packages')
            ->has('packagist')
            ->has('npm')
            ->has('analytics')
            ->has('search_console')
            ->has('registry_breakdown')
            ->has('status_breakdown')
            ->has('top_documented')
            ->has('recent_imports')
            ->has('docs_distribution'));
});

it('redirects guests to login', function (): void {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('redirects unverified users to email verification', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('verification.notice'));
});

it('withholds analytics-scoped payload from non-admin editors', function (): void {
    $editor = User::factory()->editor()->create(['email_verified_at' => now()]);

    $this->actingAs($editor)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/Index')
            ->where('can_view_analytics', false)
            ->where('analytics', null)
            ->where('search_console', null)
            ->where('totals.downloads', 0)
            ->where('totals.monthly_downloads', 0)
            ->where('totals.stars', 0)
            ->where('packagist.is_configured', false)
            ->where('npm.is_configured', false));
});

it('exposes analytics-scoped payload to admins', function (): void {
    $admin = User::factory()->admin()->create(['email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard/Index')
            ->where('can_view_analytics', true)
            ->has('analytics'));
});
