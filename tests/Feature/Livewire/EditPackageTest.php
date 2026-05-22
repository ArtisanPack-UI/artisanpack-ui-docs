<?php

use App\Jobs\ImportChangelog;
use App\Jobs\ImportWikiDocumentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Core\Setting;
use Modules\Packages\Livewire\Admin\EditPackage;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

test('import documentation dispatches job when wiki url and token are present', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('test-token-123'),
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importDocumentation')
        ->assertHasNoErrors();

    Queue::assertPushed(ImportWikiDocumentation::class, function ($job) use ($package) {
        return $job->package->id === $package->id;
    });
});

test('import documentation dispatches job and persists docs url when set', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => 'https://github.com/owner/repo/wiki',
        'docs_url' => null,
    ]);

    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('test-token-123'),
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->set('docs_url', 'https://github.com/owner/repo')
        ->call('importDocumentation')
        ->assertHasNoErrors();

    expect($package->fresh()->docs_url)->toBe('https://github.com/owner/repo');

    Queue::assertPushed(ImportWikiDocumentation::class, fn ($job) => $job->package->id === $package->id);
});

test('import documentation does not dispatch job when wiki url is missing', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => '',
    ]);

    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('test-token-123'),
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importDocumentation')
        ->assertHasNoErrors();

    Queue::assertNothingPushed();
});

test('import documentation does not dispatch job when github token is not configured', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importDocumentation')
        ->assertHasNoErrors();

    Queue::assertNothingPushed();
});

test('import changelog dispatches job when changelog url and token are present', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('test-token-123'),
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importChangelog')
        ->assertHasNoErrors();

    Queue::assertPushed(ImportChangelog::class, function ($job) use ($package) {
        return $job->package->id === $package->id;
    });
});

test('import changelog does not dispatch job when changelog url is missing', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'changelog_url' => '',
    ]);

    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('test-token-123'),
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importChangelog')
        ->assertHasNoErrors();

    Queue::assertNothingPushed();
});

test('import changelog does not dispatch job when github token is not configured', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importChangelog')
        ->assertHasNoErrors();

    Queue::assertNothingPushed();
});

test('update package rejects unsupported url', function (string $field, string $url) {
    $package = Package::factory()->create();

    Livewire::test(EditPackage::class, ['package' => $package])
        ->set('name', 'Updated Package')
        ->set('slug', 'updated-package')
        ->set('wiki_url', 'https://github.com/owner/repo/wiki')
        ->set('changelog_url', 'https://github.com/owner/repo/blob/main/CHANGELOG.md')
        ->set($field, $url)
        ->call('updatePackage')
        ->assertHasErrors([$field => 'regex']);
})->with([
    'bitbucket wiki url' => ['wiki_url', 'https://bitbucket.org/owner/repo/wiki'],
    'bitbucket changelog url' => ['changelog_url', 'https://bitbucket.org/owner/repo/CHANGELOG.md'],
]);

test('update package accepts github and gitlab urls', function () {
    $package = Package::factory()->create();

    Livewire::test(EditPackage::class, ['package' => $package])
        ->set('name', 'Updated Package')
        ->set('slug', 'updated-package')
        ->set('wiki_url', 'https://github.com/owner/repo/wiki')
        ->set('changelog_url', 'https://gitlab.com/owner/repo/-/blob/main/CHANGELOG.md')
        ->call('updatePackage')
        ->assertHasNoErrors(['wiki_url', 'changelog_url']);
});

test('wiki source computed property returns github for github urls', function () {
    $package = Package::factory()->create([
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->assertSet('wikiSource', 'github');
});

test('wiki source computed property returns gitlab for gitlab urls', function () {
    $package = Package::factory()->create([
        'wiki_url' => 'https://gitlab.com/owner/repo/-/wikis/home',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->assertSet('wikiSource', 'gitlab');
});

test('wiki source computed property returns null when wiki url is empty', function () {
    $package = Package::factory()->create([
        'wiki_url' => '',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->assertSet('wikiSource', null);
});
