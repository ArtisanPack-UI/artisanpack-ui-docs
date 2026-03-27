<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Admin\Livewire\SettingsPage;
use Modules\Core\Setting;

uses(RefreshDatabase::class);

test('it mounts with empty values when no settings exist', function () {
    Livewire::test(SettingsPage::class)
        ->assertSet('gitLabToken', '')
        ->assertSet('gitHubToken', '')
        ->assertSet('homePage', '')
        ->assertSet('googleAnalyticsId', '');
});

test('it mounts with decrypted github token when setting exists', function () {
    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('ghp_testtoken123'),
    ]);

    Livewire::test(SettingsPage::class)
        ->assertSet('gitHubToken', 'ghp_testtoken123');
});

test('it saves github token encrypted', function () {
    Livewire::test(SettingsPage::class)
        ->set('gitHubToken', 'ghp_testtoken123')
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::where('key', 'github_token')->first();
    expect($setting)->not->toBeNull();
    expect(decrypt($setting->value))->toBe('ghp_testtoken123');
});

test('it saves github pat token with github_pat_ prefix', function () {
    Livewire::test(SettingsPage::class)
        ->set('gitHubToken', 'github_pat_abc123XYZ')
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::where('key', 'github_token')->first();
    expect($setting)->not->toBeNull();
    expect(decrypt($setting->value))->toBe('github_pat_abc123XYZ');
});

test('it saves null when github token is empty', function () {
    Livewire::test(SettingsPage::class)
        ->set('gitHubToken', '')
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::where('key', 'github_token')->first();
    expect($setting->value)->toBeNull();
});

test('it validates github token format', function () {
    Livewire::test(SettingsPage::class)
        ->set('gitHubToken', 'invalid-token-format')
        ->call('save')
        ->assertHasErrors(['gitHubToken' => 'regex']);
});

test('it saves gitlab token encrypted', function () {
    Livewire::test(SettingsPage::class)
        ->set('gitLabToken', 'glpat-testtoken123')
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::where('key', 'gitlab_token')->first();
    expect($setting)->not->toBeNull();
    expect(decrypt($setting->value))->toBe('glpat-testtoken123');
});

test('it saves all settings together', function () {
    Livewire::test(SettingsPage::class)
        ->set('gitLabToken', 'glpat-testtoken')
        ->set('gitHubToken', 'ghp_testtoken')
        ->set('googleAnalyticsId', 'G-ABC123XYZ')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'gitlab_token')->first())->not->toBeNull();
    expect(Setting::where('key', 'github_token')->first())->not->toBeNull();
    expect(Setting::where('key', 'google_analytics_id')->first()->value)->toBe('G-ABC123XYZ');
});
