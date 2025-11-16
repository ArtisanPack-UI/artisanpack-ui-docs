<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

test('can view documentation page by package and slug', function () {
    $package = Package::factory()->create([
        'slug' => 'test-package',
        'name' => 'Test Package',
    ]);

    $documentation = Documentation::create([
        'slug' => 'getting-started',
        'title' => 'Getting Started',
        'content' => '# Getting Started Guide',
        'package_id' => $package->id,
    ]);

    $response = $this->get("/documentation/{$package->slug}/{$documentation->slug}");

    $response->assertStatus(200)
        ->assertSee('Getting Started')
        ->assertSee('Getting Started Guide');
});

test('can view documentation with nested slug', function () {
    $package = Package::factory()->create([
        'slug' => 'test-package',
        'name' => 'Test Package',
    ]);

    $parent = Documentation::create([
        'slug' => 'guides',
        'title' => 'Guides',
        'content' => '# Guides Overview',
        'package_id' => $package->id,
    ]);

    $documentation = Documentation::create([
        'slug' => 'guides/installation',
        'title' => 'Installation Guide',
        'content' => '# Installation Steps',
        'package_id' => $package->id,
        'parent' => $parent->id,
    ]);

    $response = $this->get("/documentation/{$package->slug}/{$documentation->slug}");

    $response->assertStatus(200)
        ->assertSee('Installation Guide')
        ->assertSee('Installation Steps');
});

test('returns 404 when package not found', function () {
    $response = $this->get('/documentation/non-existent-package/some-page');

    $response->assertStatus(404);
});

test('returns 404 when documentation not found', function () {
    $package = Package::factory()->create([
        'slug' => 'test-package',
        'name' => 'Test Package',
    ]);

    $response = $this->get("/documentation/{$package->slug}/non-existent-page");

    $response->assertStatus(404);
});

test('renders markdown content correctly', function () {
    $package = Package::factory()->create([
        'slug' => 'test-package',
        'name' => 'Test Package',
    ]);

    $documentation = Documentation::create([
        'slug' => 'markdown-test',
        'title' => 'Markdown Test',
        'content' => "## Subtitle\n\nThis is **bold** and *italic* text.\n\n- List item 1\n- List item 2",
        'package_id' => $package->id,
    ]);

    $response = $this->get("/documentation/{$package->slug}/{$documentation->slug}");

    $response->assertStatus(200)
        ->assertSee('<h2>Subtitle</h2>', false)
        ->assertSee('<strong>bold</strong>', false)
        ->assertSee('<em>italic</em>', false)
        ->assertSee('<li>List item 1</li>', false);
});
