<?php

declare(strict_types=1);

use Modules\Core\Services\NavigationService;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Page;

it('builds an empty tree when there are no pages or packages', function () {
    $service = new NavigationService;

    expect($service->buildPages())->toBe([]);
    expect($service->buildPackages())->toBe([]);
});

it('nests child pages under their parents in menu order', function () {
    $parent = Page::create([
        'title' => 'Docs',
        'slug' => 'docs',
        'content' => '',
        'menu_order' => 1,
    ]);
    Page::create([
        'title' => 'Getting Started',
        'slug' => 'getting-started',
        'content' => '',
        'parent' => $parent->id,
        'menu_order' => 1,
    ]);
    Page::create([
        'title' => 'Installation',
        'slug' => 'installation',
        'content' => '',
        'parent' => $parent->id,
        'menu_order' => 2,
    ]);

    $tree = (new NavigationService)->buildPages();

    expect($tree)->toHaveCount(1);
    expect($tree[0])
        ->toMatchArray([
            'title' => 'Docs',
            'slug' => 'docs',
        ]);
    expect($tree[0]['children'])->toHaveCount(2);
    expect($tree[0]['children'][0]['slug'])->toBe('getting-started');
    expect($tree[0]['children'][1]['slug'])->toBe('installation');
});

it('flags the top-level page whose slug matches the current URL', function () {
    Page::create(['title' => 'About', 'slug' => 'about', 'content' => '']);
    Page::create(['title' => 'Contact', 'slug' => 'contact', 'content' => '']);

    $this->get('/about');

    $tree = (new NavigationService)->buildPages();

    $about = collect($tree)->firstWhere('slug', 'about');
    $contact = collect($tree)->firstWhere('slug', 'contact');

    expect($about['isCurrentPage'])->toBeTrue();
    expect($about['active'])->toBeTrue();
    expect($contact['isCurrentPage'])->toBeFalse();
    expect($contact['active'])->toBeFalse();
});

it('builds a package menu with documentation, homepage, and changelog entries', function () {
    $package = Package::factory()->create([
        'slug' => 'artisanpack-ui-react',
        'name' => 'ArtisanPack UI React',
    ]);

    $homepage = Documentation::create([
        'slug' => 'overview',
        'title' => 'Overview',
        'content' => 'Overview content',
        'package_id' => $package->id,
    ]);

    Documentation::create([
        'slug' => 'usage',
        'title' => 'Usage',
        'content' => 'Usage content',
        'package_id' => $package->id,
    ]);

    $package->update(['homepage' => $homepage->id]);

    $menus = (new NavigationService)->buildPackages();

    expect($menus)->toHaveCount(1);
    expect($menus[0]['slug'])->toBe('artisanpack-ui-react');
    expect($menus[0]['homepage']['slug'])->toBe('overview');
    expect($menus[0]['documentation'])->toHaveCount(1);
    expect($menus[0]['documentation'][0]['slug'])->toBe('usage');
});

it('resolves ap.* icons on pages to inline SVG markup with currentColor', function () {
    Page::create([
        'title' => 'Docs',
        'slug' => 'docs',
        'content' => '',
        'icon' => 'ap.atom-simple',
    ]);

    $tree = (new NavigationService)->buildPages();

    expect($tree[0]['icon'])->toMatchArray(['type' => 'svg']);
    expect($tree[0]['icon']['markup'])
        ->toContain('<svg')
        ->toContain('fill="currentColor"');
});

it('resolves fas.* icons on pages to a Font Awesome class', function () {
    Page::create([
        'title' => 'Home',
        'slug' => 'home',
        'content' => '',
        'icon' => 'fas.house',
    ]);

    $tree = (new NavigationService)->buildPages();

    expect($tree[0]['icon'])->toBe(['type' => 'class', 'class' => 'fa-solid fa-house']);
});

it('returns null for pages that have no icon configured', function () {
    Page::create([
        'title' => 'About',
        'slug' => 'about',
        'content' => '',
        'icon' => null,
    ]);

    $tree = (new NavigationService)->buildPages();

    expect($tree[0]['icon'])->toBeNull();
});
