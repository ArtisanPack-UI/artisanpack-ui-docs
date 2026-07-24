<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Core\Setting;
use Modules\Pages\Page;

uses(RefreshDatabase::class);

function siteSettingsAdmin(): User
{
    return User::factory()->admin()->create(['email_verified_at' => now()]);
}

function siteSettingsEditor(): User
{
    return User::factory()->editor()->create(['email_verified_at' => now()]);
}

it('renders the settings page for admins', function (): void {
    $admin = siteSettingsAdmin();
    $page = Page::factory()->create(['title' => 'Landing']);
    Setting::create(['key' => 'homePage', 'value' => (string) $page->id]);
    Setting::create(['key' => 'google_analytics_id', 'value' => 'G-ABC123']);
    Setting::create(['key' => 'github_token', 'value' => encrypt('ghp_secret')]);

    $this->actingAs($admin)
        ->get(route('dashboard.settings'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $inertia) => $inertia
            ->component('Core::Admin/Settings')
            ->where('settings.home_page', $page->id)
            ->where('settings.google_analytics_id', 'G-ABC123')
            ->where('settings.has_github_token', true)
            ->where('settings.has_gitlab_token', false)
            ->where('update_url', route('dashboard.settings.update'))
            ->has('pages', 1)
        );
});

it('redirects guests from the settings page to login', function (): void {
    $this->get(route('dashboard.settings'))->assertRedirect(route('login'));
});

it('redirects unverified users from the settings page', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard.settings'))
        ->assertRedirect(route('verification.notice'));
});

it('forbids editors from viewing the settings page', function (): void {
    $this->actingAs(siteSettingsEditor())
        ->get(route('dashboard.settings'))
        ->assertForbidden();
});

it('saves the home page and google analytics id', function (): void {
    $page = Page::factory()->create();

    $this->actingAs(siteSettingsAdmin())
        ->patch(route('dashboard.settings.update'), [
            'home_page' => $page->id,
            'google_analytics_id' => 'G-XYZ789',
        ])
        ->assertRedirect(route('dashboard.settings'))
        ->assertSessionHas('success');

    expect(Setting::where('key', 'homePage')->value('value'))->toBe((string) $page->id);
    expect(Setting::where('key', 'google_analytics_id')->value('value'))->toBe('G-XYZ789');
});

it('encrypts and stores new github and gitlab tokens', function (): void {
    $this->actingAs(siteSettingsAdmin())
        ->patch(route('dashboard.settings.update'), [
            'github_token' => 'ghp_realtoken123',
            'gitlab_token' => 'glpat-realtoken123',
        ])
        ->assertRedirect(route('dashboard.settings'));

    $github = Setting::where('key', 'github_token')->first();
    $gitlab = Setting::where('key', 'gitlab_token')->first();

    expect($github)->not->toBeNull();
    expect($gitlab)->not->toBeNull();
    expect(decrypt($github->value))->toBe('ghp_realtoken123');
    expect(decrypt($gitlab->value))->toBe('glpat-realtoken123');
});

it('preserves an existing token when the field is submitted empty', function (): void {
    Setting::create(['key' => 'github_token', 'value' => encrypt('ghp_existing')]);

    $this->actingAs(siteSettingsAdmin())
        ->patch(route('dashboard.settings.update'), [
            'github_token' => '',
        ])
        ->assertRedirect(route('dashboard.settings'));

    expect(decrypt(Setting::where('key', 'github_token')->value('value')))->toBe('ghp_existing');
});

it('validates the github token format', function (): void {
    $this->actingAs(siteSettingsAdmin())
        ->from(route('dashboard.settings'))
        ->patch(route('dashboard.settings.update'), [
            'github_token' => 'not-a-real-token',
        ])
        ->assertSessionHasErrors('github_token');

    expect(Setting::where('key', 'github_token')->exists())->toBeFalse();
});

it('validates the google analytics id format', function (): void {
    $this->actingAs(siteSettingsAdmin())
        ->from(route('dashboard.settings'))
        ->patch(route('dashboard.settings.update'), [
            'google_analytics_id' => 'not-a-real-id',
        ])
        ->assertSessionHasErrors('google_analytics_id');
});

it('rejects a home page id that does not exist', function (): void {
    $this->actingAs(siteSettingsAdmin())
        ->from(route('dashboard.settings'))
        ->patch(route('dashboard.settings.update'), [
            'home_page' => 999999,
        ])
        ->assertSessionHasErrors('home_page');
});

it('forbids editors from saving settings', function (): void {
    $this->actingAs(siteSettingsEditor())
        ->patch(route('dashboard.settings.update'), [
            'google_analytics_id' => 'G-XYZ789',
        ])
        ->assertForbidden();

    expect(Setting::where('key', 'google_analytics_id')->exists())->toBeFalse();
});
