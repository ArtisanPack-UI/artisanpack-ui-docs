<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Modules\Packages\Changelog;
use Modules\Packages\Package;

it('renders the changelog viewer for a package', function () {
    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'version' => '2.0.0',
    ]);
    Changelog::create([
        'title' => 'Test Package Changelog',
        'content' => "## v1.0.0\n\n- Initial release\n\n## v2.0.0\n\n- New feature",
        'package_id' => $package->id,
    ]);

    $response = $this->get('/changelogs/test-package');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Packages::Changelog/Show')
        ->where('package.name', 'Test Package')
        ->where('package.slug', 'test-package')
        ->where('package.version', '2.0.0')
        ->where('changelog.title', 'Test Package Changelog')
        ->where('changelog.content', fn ($content) => str_contains($content, 'Initial release'))
    );
});

it('extracts per-version anchors as a nested table of contents', function () {
    $package = Package::factory()->create(['slug' => 'test-package']);
    // Version numbers below are picked to be unique across this file's
    // other tests. htmLawed (the sanitizer behind `kses()`) dedupes ids
    // across calls within one PHP process, so running two tests that
    // both produce id="v100" would silently strip the id from the
    // second — a test-only quirk since production requests hit fresh
    // workers.
    Changelog::create([
        'title' => 'Changelog',
        'content' => "## v7.0.0\n\n### Added\n\nThings\n\n## v6.0.0\n\nInitial",
        'package_id' => $package->id,
    ]);

    $this->get('/changelogs/test-package')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('changelog.content', fn ($content) => str_contains($content, 'id="v700"'))
            ->has('changelog.tableOfContents', 2)
            ->where('changelog.tableOfContents.0.text', 'v7.0.0')
            ->where('changelog.tableOfContents.0.level', 2)
            ->has('changelog.tableOfContents.0.children', 1)
            ->where('changelog.tableOfContents.0.children.0.text', 'Added')
            ->where('changelog.tableOfContents.1.text', 'v6.0.0')
        );
});

it('strips script tags from changelog content via kses', function () {
    $package = Package::factory()->create(['slug' => 'test-package']);
    Changelog::create([
        'title' => 'Changelog',
        'content' => "## v3.0.0\n\nRelease <script>alert('xss')</script> notes.",
        'package_id' => $package->id,
    ]);

    $this->get('/changelogs/test-package')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('changelog.content', fn ($content) => ! str_contains($content, '<script'))
        );
});

it('returns 404 when the package does not exist', function () {
    $this->get('/changelogs/missing-package')->assertNotFound();
});

it('returns 404 when the package has no changelog', function () {
    Package::factory()->create(['slug' => 'test-package']);

    $this->get('/changelogs/test-package')->assertNotFound();
});

it('exposes the shared sidebar navigation prop', function () {
    $package = Package::factory()->create(['slug' => 'test-package']);
    Changelog::create([
        'title' => 'Changelog',
        'content' => '## v1.0.0',
        'package_id' => $package->id,
    ]);

    $this->get('/changelogs/test-package')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('navigation.pages')
            ->has('navigation.packages')
        );
});

it('does not leave behind the Livewire public changelog component or its view', function () {
    expect(file_exists(base_path('Modules/Packages/app/Livewire/Public/Changelog.php')))->toBeFalse();
    expect(file_exists(base_path('Modules/Packages/resources/views/livewire/public/changelog.blade.php')))->toBeFalse();

    $routes = (string) file_get_contents(base_path('Modules/Packages/routes/web.php'));
    expect($routes)
        ->not->toContain('Modules\\Packages\\Livewire\\Public\\Changelog')
        ->toContain('ChangelogViewerController');
});
