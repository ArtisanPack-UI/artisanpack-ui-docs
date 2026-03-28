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
        ->assertSet('hasGitLabToken', false)
        ->assertSet('hasGitHubToken', false)
        ->assertSet('homePage', '')
        ->assertSet('googleAnalyticsId', '');
});

test('it mounts with token flags when tokens exist', function () {
    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('ghp_testtoken123'),
    ]);

    Setting::create([
        'key' => 'gitlab_token',
        'value' => encrypt('glpat-testtoken123'),
    ]);

    Livewire::test(SettingsPage::class)
        ->assertSet('gitHubToken', '')
        ->assertSet('gitLabToken', '')
        ->assertSet('hasGitHubToken', true)
        ->assertSet('hasGitLabToken', true);
});

test('it saves github token encrypted', function (string $token) {
    Livewire::test(SettingsPage::class)
        ->set('gitHubToken', $token)
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::where('key', 'github_token')->first();
    expect($setting)->not->toBeNull();
    expect(decrypt($setting->value))->toBe($token);
})->with([
    'classic token' => 'ghp_testtoken123',
    'fine-grained token' => 'github_pat_abc123XYZ',
]);

test('it does not clear existing token when saved with empty value', function () {
    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('ghp_existingtoken'),
    ]);

    Livewire::test(SettingsPage::class)
        ->set('gitHubToken', '')
        ->call('save')
        ->assertHasNoErrors();

    $setting = Setting::where('key', 'github_token')->first();
    expect(decrypt($setting->value))->toBe('ghp_existingtoken');
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
