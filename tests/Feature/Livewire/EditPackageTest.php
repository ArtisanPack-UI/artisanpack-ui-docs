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
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Setting::create([
        'key' => 'gitlab_token',
        'value' => 'test-token-123',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importDocumentation')
        ->assertHasNoErrors();

    Queue::assertPushed(ImportWikiDocumentation::class, function ($job) use ($package) {
        return $job->package->id === $package->id && $job->gitlabToken === 'test-token-123';
    });
});

test('import documentation does not dispatch job when wiki url is missing', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => '',
    ]);

    Setting::create([
        'key' => 'gitlab_token',
        'value' => 'test-token-123',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importDocumentation')
        ->assertHasNoErrors();

    Queue::assertNothingPushed();
});

test('import documentation does not dispatch job when gitlab token is not configured', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importDocumentation')
        ->assertHasNoErrors();

    Queue::assertNothingPushed();
});

test('import changelog dispatches job when changelog url and token are present', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'changelog_url' => 'https://gitlab.com/group/project/-/blob/main/CHANGELOG.md',
    ]);

    Setting::create([
        'key' => 'gitlab_token',
        'value' => 'test-token-123',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importChangelog')
        ->assertHasNoErrors();

    Queue::assertPushed(ImportChangelog::class, function ($job) use ($package) {
        return $job->package->id === $package->id && $job->gitlabToken === 'test-token-123';
    });
});

test('import changelog does not dispatch job when changelog url is missing', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'changelog_url' => '',
    ]);

    Setting::create([
        'key' => 'gitlab_token',
        'value' => 'test-token-123',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importChangelog')
        ->assertHasNoErrors();

    Queue::assertNothingPushed();
});

test('import changelog does not dispatch job when gitlab token is not configured', function () {
    Queue::fake();

    $package = Package::factory()->create([
        'changelog_url' => 'https://gitlab.com/group/project/-/blob/main/CHANGELOG.md',
    ]);

    Livewire::test(EditPackage::class, ['package' => $package])
        ->call('importChangelog')
        ->assertHasNoErrors();

    Queue::assertNothingPushed();
});
