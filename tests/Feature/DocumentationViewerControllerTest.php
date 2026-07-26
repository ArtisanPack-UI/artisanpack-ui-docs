<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

it('renders the docs viewer for a package and slug', function () {
    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'version' => '1.2.3',
        'homepage' => null,
    ]);
    Documentation::create([
        'title' => 'Getting Started',
        'slug' => 'getting-started',
        'content' => "# Getting Started\n\nWelcome.",
        'meta_description' => 'How to get started.',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $response = $this->get('/documentation/test-package/getting-started');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Packages::Documentation/Show')
        ->where('package.name', 'Test Package')
        ->where('package.slug', 'test-package')
        ->where('package.version', '1.2.3')
        ->where('doc.title', 'Getting Started')
        ->where('doc.slug', 'getting-started')
        ->where('doc.metaDescription', 'How to get started.')
        ->where('doc.content', fn ($content) => str_contains($content, 'Welcome'))
    );
});

it('renders docs with a nested slash slug', function () {
    $package = Package::factory()->create([
        'slug' => 'test-package',
        'homepage' => null,
    ]);
    $parent = Documentation::create([
        'title' => 'Guides',
        'slug' => 'guides',
        'content' => '# Guides',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);
    Documentation::create([
        'title' => 'Installation',
        'slug' => 'guides/installation',
        'content' => '# Install',
        'package_id' => $package->id,
        'parent' => $parent->id,
        'menu_order' => 0,
    ]);

    $response = $this->get('/documentation/test-package/guides/installation');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Packages::Documentation/Show')
        ->where('doc.title', 'Installation')
        ->where('doc.slug', 'guides/installation')
    );
});

it('returns 404 when the package does not exist', function () {
    $this->get('/documentation/missing-package/anything')->assertNotFound();
});

it('returns 404 when the docs slug does not belong to the package', function () {
    $one = Package::factory()->create(['slug' => 'one', 'homepage' => null]);
    $two = Package::factory()->create(['slug' => 'two', 'homepage' => null]);
    Documentation::create([
        'title' => 'Doc A',
        'slug' => 'doc-a',
        'content' => '',
        'package_id' => $one->id,
        'menu_order' => 0,
    ]);

    $this->get('/documentation/two/doc-a')->assertNotFound();
});

it('runs markdown through the TOC pipeline and kses', function () {
    $package = Package::factory()->create(['slug' => 'test-package', 'homepage' => null]);
    Documentation::create([
        'title' => 'Reference',
        'slug' => 'reference',
        'content' => "## Section\n\nBody\n\n### Detail\n\nMore\n\n## Wrap Up\n\nEnd",
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $response = $this->get('/documentation/test-package/reference');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('doc.content', fn ($content) => str_contains($content, 'id="section"'))
        ->has('doc.tableOfContents', 2)
        ->where('doc.tableOfContents.0.text', 'Section')
        ->where('doc.tableOfContents.0.level', 2)
        ->has('doc.tableOfContents.0.children', 1)
        ->where('doc.tableOfContents.0.children.0.text', 'Detail')
        ->where('doc.tableOfContents.1.text', 'Wrap Up')
    );
});

it('exposes prev and next neighbors following menu_order', function () {
    $package = Package::factory()->create(['slug' => 'test-package', 'homepage' => null]);
    Documentation::create([
        'title' => 'First',
        'slug' => 'first',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);
    Documentation::create([
        'title' => 'Second',
        'slug' => 'second',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 1,
    ]);
    Documentation::create([
        'title' => 'Third',
        'slug' => 'third',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 2,
    ]);

    $response = $this->get('/documentation/test-package/second');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('previous.title', 'First')
        ->where('previous.url', route('documentation.show', ['package' => 'test-package', 'slug' => 'first']))
        ->where('next.title', 'Third')
        ->where('next.url', route('documentation.show', ['package' => 'test-package', 'slug' => 'third']))
    );
});

it('returns null neighbors at the ends of the walk', function () {
    $package = Package::factory()->create(['slug' => 'test-package', 'homepage' => null]);
    Documentation::create([
        'title' => 'Only',
        'slug' => 'only',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $response = $this->get('/documentation/test-package/only');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('previous', null)
        ->where('next', null)
    );
});

it('strips script tags from doc content via kses', function () {
    $package = Package::factory()->create(['slug' => 'test-package', 'homepage' => null]);
    Documentation::create([
        'title' => 'Guide',
        'slug' => 'guide',
        'content' => "## Safe heading\n\nBody <script>alert('xss')</script> body.",
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $response = $this->get('/documentation/test-package/guide');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('doc.content', fn ($content) => ! str_contains($content, '<script'))
    );
});

it('returns null prev and next when viewing the package homepage doc directly', function () {
    $package = Package::factory()->create(['slug' => 'test-package']);
    $home = Documentation::create([
        'title' => 'Overview',
        'slug' => 'overview',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);
    $package->update(['homepage' => $home->id]);
    Documentation::create([
        'title' => 'One',
        'slug' => 'one',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 1,
    ]);

    $this->get('/documentation/test-package/overview')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            // The homepage doc is filtered out of the linear walk, so it
            // has no position in it — surrounding entries must not "wrap
            // around" to it either.
            ->where('previous', null)
            ->where('next', null)
        );
});

it('skips the package homepage doc from the prev/next walk', function () {
    $package = Package::factory()->create(['slug' => 'test-package']);
    $home = Documentation::create([
        'title' => 'Overview',
        'slug' => 'overview',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);
    $package->update(['homepage' => $home->id]);
    Documentation::create([
        'title' => 'One',
        'slug' => 'one',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 1,
    ]);
    Documentation::create([
        'title' => 'Two',
        'slug' => 'two',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 2,
    ]);

    $response = $this->get('/documentation/test-package/one');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        // Homepage doc would sort first by menu_order — verify it's excluded
        // so 'One' is treated as the first entry in the walk.
        ->where('previous', null)
        ->where('next.title', 'Two')
    );
});

it('exposes the shared sidebar navigation prop', function () {
    $package = Package::factory()->create(['slug' => 'test-package', 'homepage' => null]);
    Documentation::create([
        'title' => 'Doc',
        'slug' => 'doc',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $this->get('/documentation/test-package/doc')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('navigation.pages')
            ->has('navigation.packages')
        );
});

it('redirects legacy /documentation/{package}/changelog(s) to the changelog route', function () {
    Package::factory()->create(['slug' => 'test-package']);

    $this->get('/documentation/test-package/changelog')
        ->assertRedirect(route('changelog.show', ['package' => 'test-package']))
        ->assertStatus(301);

    $this->get('/documentation/test-package/changelogs')
        ->assertRedirect(route('changelog.show', ['package' => 'test-package']))
        ->assertStatus(301);
});

it('does not leave behind the Livewire public documentation component or its view', function () {
    expect(file_exists(base_path('Modules/Packages/app/Livewire/Public/Documentation.php')))->toBeFalse();
    expect(file_exists(base_path('Modules/Packages/resources/views/livewire/public/documentation.blade.php')))->toBeFalse();

    $routes = (string) file_get_contents(base_path('Modules/Packages/routes/web.php'));
    expect($routes)
        ->not->toContain('Modules\\Packages\\Livewire\\Public\\Documentation')
        ->toContain('DocumentationViewerController');
});
