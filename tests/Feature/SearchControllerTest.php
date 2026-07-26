<?php

declare(strict_types=1);

use Modules\Packages\Changelog;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Database\Factories\PageFactory;
use Modules\Pages\Page;

it('returns an empty result set for a blank query', function () {
    $response = $this->getJson('/search');

    $response
        ->assertOk()
        ->assertExactJson(['results' => []]);
});

it('trims whitespace-only queries down to empty', function () {
    $response = $this->getJson('/search?q=%20%20');

    $response
        ->assertOk()
        ->assertExactJson(['results' => []]);
});

it('matches pages by title and returns a stable link + page description', function () {
    PageFactory::new()->create([
        'title' => 'Getting Started Guide',
        'slug' => 'getting-started',
        'content' => 'Some body content.',
        'parent' => 0,
        'icon' => null,
    ]);

    $response = $this->getJson('/search?q=Getting');

    $response
        ->assertOk()
        ->assertJsonPath('results.0.name', 'Getting Started Guide')
        ->assertJsonPath('results.0.description', 'Page')
        ->assertJsonPath(
            'results.0.link',
            route('page.show', ['slug' => 'getting-started']),
        );
});

it('labels child pages with the parent breadcrumb and links to page.child', function () {
    /** @var Page $parent */
    $parent = PageFactory::new()->create([
        'title' => 'Guides',
        'slug' => 'guides',
        'parent' => 0,
    ]);

    PageFactory::new()->create([
        'title' => 'Quickstart',
        'slug' => 'quickstart',
        'parent' => $parent->id,
        'content' => 'body',
    ]);

    $response = $this->getJson('/search?q=Quickstart');

    $response
        ->assertOk()
        ->assertJsonPath('results.0.description', 'Page · Guides')
        ->assertJsonPath(
            'results.0.link',
            route('page.child', ['parentSlug' => 'guides', 'slug' => 'quickstart']),
        );
});

it('matches packages by name and points at the package homepage doc', function () {
    $package = Package::factory()->create([
        'name' => 'Nova Package',
        'slug' => 'nova-package',
        'homepage' => null,
    ]);

    $homeDoc = Documentation::create([
        'title' => 'Home',
        'slug' => 'home',
        'content' => 'Landing',
        'package_id' => $package->id,
        'parent' => 0,
        'menu_order' => 0,
    ]);

    $package->update(['homepage' => $homeDoc->id]);

    $response = $this->getJson('/search?q=Nova');

    $response
        ->assertOk()
        ->assertJsonPath('results.0.name', 'Nova Package')
        ->assertJsonPath('results.0.description', 'Package')
        ->assertJsonPath(
            'results.0.link',
            route('documentation.show', ['package' => 'nova-package', 'slug' => 'home']),
        );
});

it('falls back to the first doc when a matched package has no homepage set', function () {
    $package = Package::factory()->create([
        'name' => 'Orbit Package',
        'slug' => 'orbit-package',
        'homepage' => null,
    ]);

    Documentation::create([
        'title' => 'Zeta',
        'slug' => 'zeta',
        'content' => '',
        'package_id' => $package->id,
        'parent' => 0,
        'menu_order' => 5,
    ]);

    Documentation::create([
        'title' => 'Alpha',
        'slug' => 'alpha',
        'content' => '',
        'package_id' => $package->id,
        'parent' => 0,
        'menu_order' => 1,
    ]);

    $response = $this->getJson('/search?q=Orbit');

    $response
        ->assertOk()
        ->assertJsonPath(
            'results.0.link',
            route('documentation.show', ['package' => 'orbit-package', 'slug' => 'alpha']),
        );
});

it('drops matched packages that have no publishable documentation', function () {
    Package::factory()->create([
        'name' => 'Empty Package',
        'slug' => 'empty-package',
        'homepage' => null,
    ]);

    $response = $this->getJson('/search?q=Empty');

    $response
        ->assertOk()
        ->assertJsonPath('results', []);
});

it('matches documentation and describes it as Documentation · Package', function () {
    $package = Package::factory()->create([
        'name' => 'Solar Package',
        'slug' => 'solar-package',
        'homepage' => null,
    ]);

    Documentation::create([
        'title' => 'Deploying Solar',
        'slug' => 'deploying-solar',
        'content' => 'Body',
        'package_id' => $package->id,
        'parent' => 0,
        'menu_order' => 0,
    ]);

    $response = $this->getJson('/search?q=Deploying');

    $response
        ->assertOk()
        ->assertJsonPath('results.0.name', 'Deploying Solar')
        ->assertJsonPath('results.0.description', 'Documentation · Solar Package')
        ->assertJsonPath(
            'results.0.link',
            route('documentation.show', ['package' => 'solar-package', 'slug' => 'deploying-solar']),
        );
});

it('matches changelogs and links to the package changelog route', function () {
    $package = Package::factory()->create([
        'name' => 'Comet Package',
        'slug' => 'comet-package',
        'homepage' => null,
    ]);

    Changelog::create([
        'title' => 'Comet Changelog',
        'content' => 'Release notes',
        'package_id' => $package->id,
    ]);

    $response = $this->getJson('/search?q=Comet%20Changelog');

    $comet = collect($response->json('results'))
        ->firstWhere('description', 'Changelog · Comet Package');

    expect($comet)->not->toBeNull()
        ->and($comet['name'])->toBe('Comet Changelog')
        ->and($comet['link'])->toBe(route('changelog.show', ['package' => 'comet-package']));
});

it('resolves the icon into the shape the React overlay expects', function () {
    PageFactory::new()->create([
        'title' => 'Iconic',
        'slug' => 'iconic',
        'parent' => 0,
        'icon' => 'fas.rocket',
        'content' => '',
    ]);

    $response = $this->getJson('/search?q=Iconic');

    $response
        ->assertOk()
        ->assertJsonPath('results.0.icon.type', 'class')
        ->assertJsonPath('results.0.icon.class', 'fa-solid fa-rocket');
});

it('caps results per type at ten so a hot query cannot flood the overlay', function () {
    for ($i = 0; $i < 15; $i++) {
        PageFactory::new()->create([
            'title' => "Flood Page {$i}",
            'slug' => "flood-{$i}",
            'parent' => 0,
            'content' => '',
        ]);
    }

    $response = $this->getJson('/search?q=Flood');

    $response
        ->assertOk()
        ->assertJsonCount(10, 'results');
});
