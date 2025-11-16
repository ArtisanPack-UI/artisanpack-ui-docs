<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Pages\Livewire\Public\Page as PageComponent;
use Modules\Pages\Page;

uses(RefreshDatabase::class);

test('page with headings generates table of contents', function () {
    $page = Page::create([
        'title' => 'Test Page',
        'slug' => 'test-page',
        'content' => '<h2>Introduction</h2><p>Content here</p><h3>Getting Started</h3><p>More content</p><h2>Configuration</h2><p>Config info</p>',
    ]);

    Livewire::test(PageComponent::class, ['slug' => 'test-page'])
        ->assertSet('tableOfContents', function ($toc) {
            return count($toc) === 2 // Two top-level headings
                && $toc[0]['text'] === 'Introduction'
                && $toc[0]['level'] === 2
                && count($toc[0]['children']) === 1 // One child under Introduction
                && $toc[0]['children'][0]['text'] === 'Getting Started'
                && $toc[1]['text'] === 'Configuration';
        });
});

test('page content has IDs added to headings', function () {
    $page = Page::create([
        'title' => 'Test Page',
        'slug' => 'test-page',
        'content' => '<h2>Getting Started</h2><p>Content here</p>',
    ]);

    Livewire::test(PageComponent::class, ['slug' => 'test-page'])
        ->assertSet('content', function ($content) {
            return str_contains($content, 'id="getting-started"');
        });
});
