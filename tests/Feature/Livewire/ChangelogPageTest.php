<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Packages\Changelog;
use Modules\Packages\Livewire\Public\Changelog as ChangelogComponent;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

test('changelog page can be rendered', function () {
    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
    ]);

    Changelog::create([
        'title' => 'Test Package Changelog',
        'content' => '## Version 1.0.0\n- Initial release',
        'package_id' => $package->id,
    ]);

    $response = $this->get('/changelogs/test-package');

    $response->assertSuccessful();
    $response->assertSeeLivewire(ChangelogComponent::class);
});

test('changelog page displays correct content', function () {
    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
    ]);

    Changelog::create([
        'title' => 'Test Package Changelog',
        'content' => '## Version 1.0.0\n- Initial release\n\n## Version 2.0.0\n- New feature',
        'package_id' => $package->id,
    ]);

    Livewire::test(ChangelogComponent::class, ['package' => 'test-package'])
        ->assertSee('Test Package Changelog')
        ->assertSee('Version 1.0.0')
        ->assertSee('Initial release')
        ->assertSee('Version 2.0.0')
        ->assertSee('New feature');
});

test('changelog page returns 404 for non-existent package', function () {
    $this->get('/changelogs/non-existent-package')
        ->assertNotFound();
});

test('changelog page returns 404 when package has no changelog', function () {
    Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
    ]);

    $this->get('/changelogs/test-package')
        ->assertNotFound();
});
