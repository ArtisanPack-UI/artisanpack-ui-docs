<?php

use Modules\Core\Services\TableOfContentsService;

test('processes markdown content and extracts headings', function () {
    $service = new TableOfContentsService;

    $markdown = "## Introduction\n\nSome content here.\n\n### Getting Started\n\nMore content.\n\n## Configuration\n\nConfig info.";

    $result = $service->process($markdown, isMarkdown: true);

    expect($result['headings'])->toHaveCount(3)
        ->and($result['headings'][0]['text'])->toBe('Introduction')
        ->and($result['headings'][0]['level'])->toBe(2)
        ->and($result['headings'][1]['text'])->toBe('Getting Started')
        ->and($result['headings'][1]['level'])->toBe(3)
        ->and($result['headings'][2]['text'])->toBe('Configuration')
        ->and($result['headings'][2]['level'])->toBe(2);
});

test('processes HTML content and extracts headings', function () {
    $service = new TableOfContentsService;

    $html = '<h2>Introduction</h2><p>Content</p><h3>Subsection</h3><p>More content</p>';

    $result = $service->process($html, isMarkdown: false);

    expect($result['headings'])->toHaveCount(2)
        ->and($result['headings'][0]['text'])->toBe('Introduction')
        ->and($result['headings'][0]['level'])->toBe(2)
        ->and($result['headings'][1]['text'])->toBe('Subsection')
        ->and($result['headings'][1]['level'])->toBe(3);
});

test('adds IDs to headings', function () {
    $service = new TableOfContentsService;

    $html = '<h2>Getting Started</h2><p>Content</p>';

    $result = $service->process($html, isMarkdown: false);

    expect($result['content'])->toContain('id="getting-started"')
        ->and($result['headings'][0]['id'])->toBe('getting-started');
});

test('preserves existing heading IDs', function () {
    $service = new TableOfContentsService;

    $html = '<h2 id="custom-id">Getting Started</h2><p>Content</p>';

    $result = $service->process($html, isMarkdown: false);

    expect($result['content'])->toContain('id="custom-id"')
        ->and($result['headings'][0]['id'])->toBe('custom-id');
});

test('builds nested table of contents structure', function () {
    $service = new TableOfContentsService;

    $headings = [
        ['id' => 'intro', 'text' => 'Introduction', 'level' => 2],
        ['id' => 'getting-started', 'text' => 'Getting Started', 'level' => 3],
        ['id' => 'installation', 'text' => 'Installation', 'level' => 4],
        ['id' => 'config', 'text' => 'Configuration', 'level' => 2],
    ];

    $nested = $service->buildNestedStructure($headings);

    expect($nested)->toHaveCount(2)
        ->and($nested[0]['id'])->toBe('intro')
        ->and($nested[0]['children'])->toHaveCount(1)
        ->and($nested[0]['children'][0]['id'])->toBe('getting-started')
        ->and($nested[0]['children'][0]['children'])->toHaveCount(1)
        ->and($nested[0]['children'][0]['children'][0]['id'])->toBe('installation')
        ->and($nested[1]['id'])->toBe('config');
});

test('handles special characters in heading text', function () {
    $service = new TableOfContentsService;

    $html = '<h2>What\'s New? (v2.0)</h2>';

    $result = $service->process($html, isMarkdown: false);

    expect($result['headings'][0]['text'])->toBe('What\'s New? (v2.0)')
        ->and($result['headings'][0]['id'])->toBe('whats-new-v20');
});

test('handles empty content gracefully', function () {
    $service = new TableOfContentsService;

    $result = $service->process('', isMarkdown: false);

    expect($result['headings'])->toBeEmpty()
        ->and($result['content'])->toBe('');
});

test('builds empty nested structure for no headings', function () {
    $service = new TableOfContentsService;

    $nested = $service->buildNestedStructure([]);

    expect($nested)->toBeEmpty();
});
