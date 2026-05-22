<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Packages\Livewire\Admin\AddPackage;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

test('add package creates package with valid data', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('wiki_url', 'https://github.com/owner/repo/wiki')
        ->set('changelog_url', 'https://github.com/owner/repo/blob/main/CHANGELOG.md')
        ->set('icon', 'ap.puzzle')
        ->set('version', '1.0.0')
        ->set('package_registry', 'packagist')
        ->call('addPackage')
        ->assertHasNoErrors();

    expect(Package::where('slug', 'test-package')->exists())->toBeTrue();
});

test('add package creates package with docs url and no wiki url', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Docs Package')
        ->set('slug', 'docs-package')
        ->set('wiki_url', '')
        ->set('docs_url', 'https://github.com/owner/repo')
        ->set('changelog_url', 'https://github.com/owner/repo/blob/main/CHANGELOG.md')
        ->call('addPackage')
        ->assertHasNoErrors();

    expect(Package::where('slug', 'docs-package')->first()->docs_url)
        ->toBe('https://github.com/owner/repo');
});

test('add package requires a wiki url or docs url', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'No Source')
        ->set('slug', 'no-source')
        ->set('wiki_url', '')
        ->set('docs_url', '')
        ->set('changelog_url', 'https://github.com/owner/repo/blob/main/CHANGELOG.md')
        ->call('addPackage')
        ->assertHasErrors(['wiki_url' => 'required_without', 'docs_url' => 'required_without']);
});

test('add package rejects a non-github docs url', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Gitlab Docs')
        ->set('slug', 'gitlab-docs')
        ->set('docs_url', 'https://gitlab.com/owner/repo')
        ->set('changelog_url', 'https://github.com/owner/repo/blob/main/CHANGELOG.md')
        ->call('addPackage')
        ->assertHasErrors(['docs_url' => 'regex']);
});

test('add package auto-generates slug from name', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'My Great Package')
        ->assertSet('slug', 'my-great-package');
});

test('add package requires name', function () {
    Livewire::test(AddPackage::class)
        ->set('name', '')
        ->set('slug', 'test-package')
        ->call('addPackage')
        ->assertHasErrors(['name' => 'required']);
});

test('add package requires slug', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', '')
        ->call('addPackage')
        ->assertHasErrors(['slug' => 'required']);
});

test('add package rejects non-github and non-gitlab wiki url', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('wiki_url', 'https://bitbucket.org/owner/repo/wiki')
        ->set('changelog_url', 'https://github.com/owner/repo/blob/main/CHANGELOG.md')
        ->call('addPackage')
        ->assertHasErrors(['wiki_url' => 'regex']);
});

test('add package rejects non-github and non-gitlab changelog url', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('wiki_url', 'https://github.com/owner/repo/wiki')
        ->set('changelog_url', 'https://bitbucket.org/owner/repo/blob/main/CHANGELOG.md')
        ->call('addPackage')
        ->assertHasErrors(['changelog_url' => 'regex']);
});

test('add package accepts valid url combinations', function (string $wikiUrl, string $changelogUrl) {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('wiki_url', $wikiUrl)
        ->set('changelog_url', $changelogUrl)
        ->call('addPackage')
        ->assertHasNoErrors(['wiki_url', 'changelog_url']);
})->with([
    'github wiki and changelog' => [
        'https://github.com/owner/repo/wiki',
        'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ],
    'gitlab wiki and changelog' => [
        'https://gitlab.com/owner/repo/-/wikis/home',
        'https://gitlab.com/owner/repo/-/blob/main/CHANGELOG.md',
    ],
    'github wiki with raw githubusercontent changelog' => [
        'https://github.com/owner/repo/wiki',
        'https://raw.githubusercontent.com/owner/repo/main/CHANGELOG.md',
    ],
]);

test('add package validates package registry is valid option', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('package_registry', 'invalid')
        ->call('addPackage')
        ->assertHasErrors(['package_registry' => 'in']);
});
