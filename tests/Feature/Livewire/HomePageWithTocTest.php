<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Core\Livewire\HomePage;
use Modules\Core\Setting;
use Modules\Pages\Page;

uses(RefreshDatabase::class);

test('home page with headings generates table of contents', function () {
    $page = Page::create([
        'title' => 'Welcome to Our Site',
        'slug' => 'home',
        'content' => '<h2>About Us</h2><p>Content here</p><h3>Our Mission</h3><p>More content</p><h2>Services</h2><p>Services info</p>',
    ]);

    Setting::create([
        'key' => 'homePage',
        'value' => $page->id,
    ]);

    Livewire::test(HomePage::class)
        ->assertSet('tableOfContents', function ($toc) {
            return count($toc) === 2 // Two top-level headings
                && $toc[0]['text'] === 'About Us'
                && $toc[0]['level'] === 2
                && count($toc[0]['children']) === 1 // One child under About Us
                && $toc[0]['children'][0]['text'] === 'Our Mission'
                && $toc[1]['text'] === 'Services';
        });
});

test('home page content has IDs added to headings', function () {
    $page = Page::create([
        'title' => 'Welcome',
        'slug' => 'home',
        'content' => '<h2>Our Story</h2><p>Content here</p>',
    ]);

    Setting::create([
        'key' => 'homePage',
        'value' => $page->id,
    ]);

    Livewire::test(HomePage::class)
        ->assertSet('content', function ($content) {
            return str_contains($content, 'id="our-story"');
        });
});

test('home page without setting has empty table of contents', function () {
    Livewire::test(HomePage::class)
        ->assertSet('tableOfContents', [])
        ->assertSet('content', '');
});
