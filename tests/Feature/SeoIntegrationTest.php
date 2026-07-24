<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Modules\Packages\Changelog;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Page;

it('shares default seo props on every inertia response', function () {
    Page::create([
        'title' => 'About',
        'slug' => 'about',
        'content' => '<p>About us.</p>',
    ]);

    $this->get('/about')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('seo')
        ->has('seo.title')
        ->has('seo.canonical')
        ->has('seo.robots')
        ->has('seo.openGraph')
        ->has('seo.twitter')
        ->has('seo.hreflang')
        ->has('seo.jsonLd')
    );
});

it('builds per-page seo from the resolved page model', function () {
    Page::create([
        'title' => 'About',
        'slug' => 'about',
        'content' => '<p>About us.</p>',
        'meta_description' => 'Who we are.',
    ]);

    $this->get('/about')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('seo.title', fn ($title) => str_contains($title, 'About'))
        ->where('seo.canonical', url('/about'))
        ->where('seo.description', 'Who we are.')
    );
});

it('builds per-page seo from the resolved documentation model', function () {
    $package = Package::factory()->create(['slug' => 'core', 'name' => 'Core']);
    Documentation::create([
        'package_id' => $package->id,
        'title' => 'Getting Started',
        'slug' => 'getting-started',
        'parent' => 0,
        'menu_order' => 0,
        'content' => '# Intro',
    ]);

    $this->get('/documentation/core/getting-started')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('seo.title', fn ($title) => str_contains($title, 'Getting Started'))
            ->where('seo.canonical', url('/documentation/core/getting-started'))
        );
});

it('builds per-page seo from the resolved changelog model', function () {
    $package = Package::factory()->create(['slug' => 'core', 'name' => 'Core']);
    Changelog::create([
        'package_id' => $package->id,
        'title' => 'Changelog',
        'content' => '## 1.0.0',
    ]);

    $this->get('/changelogs/core')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('seo.canonical', url('/changelogs/core'))
        );
});

it('serves the seo package sitemap at /sitemap.xml with entries from every provider', function () {
    $page = Page::create([
        'title' => 'About',
        'slug' => 'about',
        'content' => '<p>About us.</p>',
    ]);
    $package = Package::factory()->create(['slug' => 'core', 'name' => 'Core']);
    $doc = Documentation::create([
        'package_id' => $package->id,
        'title' => 'Getting Started',
        'slug' => 'getting-started',
        'parent' => 0,
        'menu_order' => 0,
        'content' => '# Intro',
    ]);
    Changelog::create([
        'package_id' => $package->id,
        'title' => 'Changelog',
        'content' => '## 1.0.0',
    ]);

    $response = $this->get('/sitemap.xml');
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');

    $body = $response->getContent();

    expect($body)->toContain($page->getUrl());
    expect($body)->toContain($doc->getUrl());
    expect($body)->toContain(route('changelog.show', ['package' => $package->slug]));
});
