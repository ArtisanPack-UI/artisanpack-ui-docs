<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Modules\Core\Setting;
use Modules\Pages\Page;

it('renders the Inertia home page without a configured home setting', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Core::Home')
        ->where('page.title', '')
        ->where('page.metaDescription', '')
        ->where('page.content', '')
        ->where('page.tableOfContents', [])
    );
});

it('renders the configured home page with sanitized content and a nested TOC', function () {
    $homePage = Page::create([
        'title' => 'Welcome to ArtisanPack',
        'slug' => 'home',
        'content' => '<h2>About Us</h2><p>Content here</p><h3>Our Mission</h3><p>More</p><h2>Services</h2><p>Info</p>',
        'meta_description' => 'The friendly toolkit for Laravel artisans.',
    ]);

    Setting::create(['key' => 'homePage', 'value' => (string) $homePage->id]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Core::Home')
        ->where('page.title', 'Welcome to ArtisanPack')
        ->where('page.metaDescription', 'The friendly toolkit for Laravel artisans.')
        ->where('page.content', fn ($content) => str_contains($content, 'id="about-us"'))
        ->has('page.tableOfContents', 2)
        ->where('page.tableOfContents.0.text', 'About Us')
        ->where('page.tableOfContents.0.level', 2)
        ->has('page.tableOfContents.0.children', 1)
        ->where('page.tableOfContents.0.children.0.text', 'Our Mission')
        ->where('page.tableOfContents.1.text', 'Services')
    );
});

it('exposes the shared sidebar navigation prop', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Core::Home')
        ->has('navigation.pages')
        ->has('navigation.packages')
    );
});

it('runs page content through the kses sanitizer before shipping to the client', function () {
    $homePage = Page::create([
        'title' => 'Home',
        'slug' => 'home',
        'content' => '<p>Body copy.</p>',
    ]);

    Setting::create(['key' => 'homePage', 'value' => (string) $homePage->id]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('page.content', fn ($content) => str_contains($content, 'Body copy.'))
    );
});

it('does not delegate the Livewire HomePage class anywhere in the module', function () {
    expect(file_exists(base_path('Modules/Core/app/Livewire/HomePage.php')))->toBeFalse();
    expect(file_exists(base_path('Modules/Core/resources/views/livewire/home-page.blade.php')))->toBeFalse();

    $routes = (string) file_get_contents(base_path('Modules/Core/routes/web.php'));
    expect($routes)
        ->not->toContain('Modules\\Core\\Livewire\\HomePage')
        ->toContain('HomePageController');
});
