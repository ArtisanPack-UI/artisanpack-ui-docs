<?php

use App\Jobs\ImportWikiDocumentation;
use App\Services\GitHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Modules\Core\Setting;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('test-token'),
    ]);
});

function mockWikiPages(array $pages): void
{
    $mock = Mockery::mock(GitHubService::class);
    $mock->shouldReceive('getWikiPagesWithContent')->andReturn($pages);
    app()->bind(GitHubService::class, fn () => $mock);
}

test('imports wiki documentation successfully', function () {
    Log::shouldReceive('info')->times(4);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    mockWikiPages([
        ['slug' => 'home', 'title' => 'home', 'content' => "# Welcome to the Documentation\n\nSome content here."],
        ['slug' => 'installation', 'title' => 'installation', 'content' => "# Installation Guide\n\nInstallation steps."],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    expect(Documentation::count())->toBe(2);

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('Welcome to the Documentation')
        ->and($homeDoc->content)->not->toContain('# Welcome to the Documentation')
        ->and($homeDoc->content)->toContain('Some content here.')
        ->and($homeDoc->package_id)->toBe($package->id);

    $installDoc = Documentation::where('slug', 'installation')->first();
    expect($installDoc->title)->toBe('Installation Guide')
        ->and($installDoc->content)->not->toContain('# Installation Guide')
        ->and($installDoc->content)->toContain('Installation steps.')
        ->and($installDoc->package_id)->toBe($package->id);
});

test('updates existing documentation when re-importing', function () {
    Log::shouldReceive('info')->times(3);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    Documentation::create([
        'slug' => 'home',
        'title' => 'Old Title',
        'content' => 'Old content',
        'package_id' => $package->id,
    ]);

    mockWikiPages([
        ['slug' => 'home', 'title' => 'Updated Title', 'content' => 'Updated content'],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    expect(Documentation::count())->toBe(1);

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('Updated Title')
        ->and($homeDoc->content)->toBe('Updated content');
});

test('logs error and throws exception on failure', function () {
    Log::shouldReceive('error')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    $mock = Mockery::mock(GitHubService::class);
    $mock->shouldReceive('getWikiPagesWithContent')
        ->andThrow(new \Exception('Failed to clone wiki repository'));
    app()->bind(GitHubService::class, fn () => $mock);

    $job = new ImportWikiDocumentation($package);
    $job->handle();
})->throws(\Exception::class);

test('throws exception when github token is not configured', function () {
    Log::shouldReceive('error')->once();

    Setting::where('key', 'github_token')->delete();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();
})->throws(\Exception::class, 'GitHub token not configured');

test('extracts title from YAML front matter', function () {
    Log::shouldReceive('info')->times(3);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    mockWikiPages([
        ['slug' => 'home', 'title' => 'home', 'content' => "---\ntitle: Custom Title\n---\n\n# Welcome to the Documentation\n\nContent here."],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('Custom Title')
        ->and($homeDoc->content)->not->toContain('title:')
        ->and($homeDoc->content)->not->toContain('---')
        ->and($homeDoc->content)->not->toContain('# Welcome to the Documentation')
        ->and($homeDoc->content)->toContain('Content here.');
});

test('sets parent relationships for subpages and keeps full slugs', function () {
    Log::shouldReceive('info')->zeroOrMoreTimes();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    mockWikiPages([
        ['slug' => 'guides', 'title' => 'guides', 'content' => '# Guides Overview'],
        ['slug' => 'guides/usage', 'title' => 'usage', 'content' => '# How to use'],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    expect(Documentation::count())->toBe(2);

    $parent = Documentation::where('slug', 'guides')->first();
    expect($parent)->not->toBeNull()
        ->and($parent->parent)->toBeNull();

    $child = Documentation::where('slug', 'guides/usage')->first();
    expect($child)->not->toBeNull()
        ->and($child->parent)->toBe($parent->id);
});

test('updates internal documentation links', function () {
    Log::shouldReceive('info')->times(3);

    config(['app.url' => 'https://example.com']);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    mockWikiPages([
        ['slug' => 'home', 'title' => 'home', 'content' => 'Check out the [installation guide](installation) and [usage guide](guides/usage).'],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->content)
        ->toContain('https://example.com/documentation/test-package/installation')
        ->toContain('https://example.com/documentation/test-package/guides/usage');
});

test('rewrites absolute GitHub wiki URLs to internal links', function () {
    Log::shouldReceive('info')->times(3);

    config(['app.url' => 'https://example.com']);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    mockWikiPages([
        ['slug' => 'home', 'title' => 'home', 'content' => 'See [page](https://github.com/owner/repo/wiki/Getting-Started) and [other](https://github.com/owner/repo/wiki/API-Reference).'],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->content)
        ->toContain('https://example.com/documentation/test-package/Getting-Started')
        ->toContain('https://example.com/documentation/test-package/API-Reference')
        ->not->toContain('github.com');
});

test('extracts title from H1 header when no YAML front matter', function () {
    Log::shouldReceive('info')->times(3);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    mockWikiPages([
        ['slug' => 'home', 'title' => 'home', 'content' => "# Welcome to the Documentation\n\nThis is the content."],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('Welcome to the Documentation')
        ->and($homeDoc->content)->not->toContain('# Welcome to the Documentation')
        ->and($homeDoc->content)->toContain('This is the content.');
});

test('uses title case GitHub title when no YAML or H1 exists', function () {
    Log::shouldReceive('info')->times(3);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    mockWikiPages([
        ['slug' => 'home', 'title' => 'getting started with the package', 'content' => 'This is the content without any headers.'],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('Getting Started with the Package')
        ->and($homeDoc->content)->toBe('This is the content without any headers.');
});

test('YAML title takes precedence over H1 header', function () {
    Log::shouldReceive('info')->times(3);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
    ]);

    mockWikiPages([
        ['slug' => 'home', 'title' => 'home', 'content' => "---\ntitle: YAML Title\n---\n\n# H1 Title\n\nContent here."],
    ]);

    $job = new ImportWikiDocumentation($package);
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('YAML Title')
        ->and($homeDoc->content)->not->toContain('---')
        ->and($homeDoc->content)->not->toContain('# H1 Title')
        ->and($homeDoc->content)->toContain('Content here.');
});
