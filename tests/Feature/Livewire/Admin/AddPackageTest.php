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

test('add package accepts github wiki url', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('wiki_url', 'https://github.com/owner/repo/wiki')
        ->set('changelog_url', 'https://github.com/owner/repo/blob/main/CHANGELOG.md')
        ->call('addPackage')
        ->assertHasNoErrors(['wiki_url', 'changelog_url']);
});

test('add package accepts gitlab wiki url', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('wiki_url', 'https://gitlab.com/owner/repo/-/wikis/home')
        ->set('changelog_url', 'https://gitlab.com/owner/repo/-/blob/main/CHANGELOG.md')
        ->call('addPackage')
        ->assertHasNoErrors(['wiki_url', 'changelog_url']);
});

test('add package accepts raw githubusercontent url for changelog', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('wiki_url', 'https://github.com/owner/repo/wiki')
        ->set('changelog_url', 'https://raw.githubusercontent.com/owner/repo/main/CHANGELOG.md')
        ->call('addPackage')
        ->assertHasNoErrors(['changelog_url']);
});

test('add package validates package registry is valid option', function () {
    Livewire::test(AddPackage::class)
        ->set('name', 'Test Package')
        ->set('slug', 'test-package')
        ->set('package_registry', 'invalid')
        ->call('addPackage')
        ->assertHasErrors(['package_registry' => 'in']);
});
