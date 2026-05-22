<?php

use App\Services\GitHubDocsService;

function makeDocsTree(array $files): string
{
    $base = sys_get_temp_dir().'/docs-test-'.bin2hex(random_bytes(6));
    mkdir($base, 0700, true);

    foreach ($files as $relativePath => $contents) {
        $full = $base.'/'.$relativePath;
        $dir = dirname($full);

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        file_put_contents($full, $contents);
    }

    return $base;
}

function removeDocsTree(string $path): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($path);
}

beforeEach(function () {
    $this->service = new GitHubDocsService('test-token');
});

afterEach(function () {
    if (isset($this->docsPath) && is_dir($this->docsPath)) {
        removeDocsTree($this->docsPath);
    }
});

test('builds hierarchical slugs from subdirectory structure', function () {
    $this->docsPath = makeDocsTree([
        'getting-started.md' => '# Getting Started',
        'usage/file-uploads.md' => '# File Uploads',
        'usage/notifications.md' => '# Notifications',
    ]);

    $pages = collect($this->service->collectPages($this->docsPath));
    $slugs = $pages->pluck('slug')->sort()->values()->all();

    expect($slugs)->toBe(['getting-started', 'usage/file-uploads', 'usage/notifications']);
});

test('normalizes title-cased filenames to kebab-case slugs', function () {
    $this->docsPath = makeDocsTree([
        'Quick-Start.md' => '# Quick Start',
        'admin/Menu-and-Pages.md' => '# Menu and Pages',
    ]);

    $slugs = collect($this->service->collectPages($this->docsPath))->pluck('slug')->sort()->values()->all();

    expect($slugs)->toBe(['admin/menu-and-pages', 'quick-start']);
});

test('index file inside a directory becomes the section parent slug', function () {
    $this->docsPath = makeDocsTree([
        'guides/index.md' => '# Guides',
        'guides/usage.md' => '# Usage',
    ]);

    $slugs = collect($this->service->collectPages($this->docsPath))->pluck('slug')->sort()->values()->all();

    expect($slugs)->toBe(['guides', 'guides/usage']);
});

test('prefers index over in-directory same-name and root same-name files', function () {
    $this->docsPath = makeDocsTree([
        'advanced.md' => "# Advanced Root\n\nRoot version.",
        'advanced/advanced.md' => "# Advanced In Dir\n\nIn-dir version.",
        'advanced/index.md' => "# Advanced Index\n\nIndex version.",
        'advanced/webhooks.md' => '# Webhooks',
    ]);

    $pages = collect($this->service->collectPages($this->docsPath));

    expect($pages->pluck('slug')->sort()->values()->all())->toBe(['advanced', 'advanced/webhooks']);

    $advanced = $pages->firstWhere('slug', 'advanced');
    expect($advanced['content'])->toContain('Index version.');
});

test('in-directory same-name file wins over root same-name file when no index exists', function () {
    $this->docsPath = makeDocsTree([
        'advanced.md' => "# Advanced Root\n\nRoot version.",
        'advanced/advanced.md' => "# Advanced In Dir\n\nIn-dir version.",
    ]);

    $pages = collect($this->service->collectPages($this->docsPath));
    $advanced = $pages->firstWhere('slug', 'advanced');

    expect($pages->pluck('slug')->all())->toBe(['advanced'])
        ->and($advanced['content'])->toContain('In-dir version.');
});

test('uses root same-name file as parent when no directory same-name file exists', function () {
    $this->docsPath = makeDocsTree([
        'advanced.md' => "# Advanced\n\nRoot version.",
        'advanced/webhooks.md' => '# Webhooks',
    ]);

    $pages = collect($this->service->collectPages($this->docsPath));
    $advanced = $pages->firstWhere('slug', 'advanced');

    expect($advanced)->not->toBeNull()
        ->and($advanced['content'])->toContain('Root version.');
});

test('skips wiki chrome and hidden files', function () {
    $this->docsPath = makeDocsTree([
        '_Sidebar.md' => 'navigation',
        '_Footer.md' => 'footer',
        'home.md' => '# Home',
    ]);

    $slugs = collect($this->service->collectPages($this->docsPath))->pluck('slug')->all();

    expect($slugs)->toBe(['home']);
});

test('skips files in built-in ignored directories', function () {
    $this->docsPath = makeDocsTree([
        'home.md' => '# Home',
        'plans/01-roadmap.md' => '# Roadmap',
        'design/ux.md' => '# UX',
        'benchmarks/baseline.md' => '# Baseline',
    ]);

    $slugs = collect($this->service->collectPages($this->docsPath))->pluck('slug')->all();

    expect($slugs)->toBe(['home']);
});

test('skips files matching .docsignore patterns', function () {
    $this->docsPath = makeDocsTree([
        '.docsignore' => "*-plan.md\ndeveloper/Skipped-Tests.md\n",
        'home.md' => '# Home',
        'install-command-plan.md' => '# Plan',
        'developer/Skipped-Tests.md' => '# Skipped',
        'developer/traits.md' => '# Traits',
    ]);

    $slugs = collect($this->service->collectPages($this->docsPath))->pluck('slug')->sort()->values()->all();

    expect($slugs)->toBe(['developer/traits', 'home']);
});

test('skips files marked draft or hidden in front matter', function () {
    $this->docsPath = makeDocsTree([
        'home.md' => "---\ntitle: Home\n---\n# Home",
        'wip.md' => "---\ntitle: WIP\ndraft: true\n---\n# WIP",
        'secret.md' => "---\ntitle: Secret\nhidden: true\n---\n# Secret",
    ]);

    $slugs = collect($this->service->collectPages($this->docsPath))->pluck('slug')->all();

    expect($slugs)->toBe(['home']);
});

test('returns raw content including front matter', function () {
    $this->docsPath = makeDocsTree([
        'home.md' => "---\ntitle: Home Page\n---\n# Home\n\nWelcome.",
    ]);

    $page = collect($this->service->collectPages($this->docsPath))->firstWhere('slug', 'home');

    expect($page['content'])->toContain('title: Home Page')
        ->and($page['content'])->toContain('Welcome.');
});

test('maps a root index file to the home slug', function () {
    $this->docsPath = makeDocsTree([
        'index.md' => '# Welcome',
        'usage.md' => '# Usage',
    ]);

    $slugs = collect($this->service->collectPages($this->docsPath))->pluck('slug')->sort()->values()->all();

    expect($slugs)->toBe(['home', 'usage']);
});
