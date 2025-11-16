<?php

use App\Jobs\ImportWikiDocumentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

test('imports wiki documentation successfully', function () {
    Log::shouldReceive('info')->times(4);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'home', 'title' => 'Home'],
            ['slug' => 'installation', 'title' => 'Installation'],
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/home' => Http::response([
            'slug' => 'home',
            'title' => 'Home',
            'content' => "# Welcome to the Documentation\n\nSome content here.",
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/installation' => Http::response([
            'slug' => 'installation',
            'title' => 'Installation',
            'content' => "# Installation Guide\n\nInstallation steps.",
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
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
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Documentation::create([
        'slug' => 'home',
        'title' => 'Old Title',
        'content' => 'Old content',
        'package_id' => $package->id,
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'home', 'title' => 'Home'],
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/home' => Http::response([
            'slug' => 'home',
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
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
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response(['error' => 'Not found'], 404),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
    $job->handle();
})->throws(\Exception::class);

test('extracts title from YAML front matter', function () {
    Log::shouldReceive('info')->times(3);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'home', 'title' => 'Home'],
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/home' => Http::response([
            'slug' => 'home',
            'title' => 'Home',
            'content' => "---\ntitle: Custom Title\n---\n\n# Welcome to the Documentation\n\nContent here.",
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('Custom Title')
        ->and($homeDoc->content)->not->toContain('title:')
        ->and($homeDoc->content)->not->toContain('---')
        ->and($homeDoc->content)->not->toContain('# Welcome to the Documentation')
        ->and($homeDoc->content)->toContain('Content here.');
});

test('sets parent relationships for subpages and keeps full slugs', function () {
    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'guides', 'title' => 'Guides'],
            ['slug' => 'guides/usage', 'title' => 'Usage'],
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/guides' => Http::response([
            'slug' => 'guides',
            'title' => 'Guides',
            'content' => '# Guides Overview',
        ], 200),
        // URL encoded slug: guides/usage becomes guides%2Fusage
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/guides%2Fusage' => Http::response([
            'slug' => 'guides/usage',
            'title' => 'Usage',
            'content' => '# How to use',
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
    $job->handle();

    expect(Documentation::count())->toBe(2);

    $parent = Documentation::where('slug', 'guides')->first();
    expect($parent)->not->toBeNull()
        ->and($parent->parent)->toBeNull();

    // Child should keep full slug "guides/usage", not shortened to "usage"
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
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'home', 'title' => 'Home'],
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/home' => Http::response([
            'slug' => 'home',
            'title' => 'Home',
            'content' => 'Check out the [installation guide](installation) and [usage guide](guides/usage).',
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->content)
        ->toContain('https://example.com/documentation/test-package/installation')
        ->toContain('https://example.com/documentation/test-package/guides/usage');
});

test('extracts title from H1 header when no YAML front matter', function () {
    Log::shouldReceive('info')->times(3);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'home', 'title' => 'GitLab Title'],
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/home' => Http::response([
            'slug' => 'home',
            'title' => 'GitLab Title',
            'content' => "# Welcome to the Documentation\n\nThis is the content.",
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('Welcome to the Documentation')
        ->and($homeDoc->content)->not->toContain('# Welcome to the Documentation')
        ->and($homeDoc->content)->toContain('This is the content.');
});

test('uses title case GitLab title when no YAML or H1 exists', function () {
    Log::shouldReceive('info')->times(3);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'slug' => 'test-package',
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'home', 'title' => 'getting started with the package'],
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/home' => Http::response([
            'slug' => 'home',
            'title' => 'getting started with the package',
            'content' => 'This is the content without any headers.',
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
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
        'wiki_url' => 'https://gitlab.com/group/project/-/wikis',
    ]);

    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'home', 'title' => 'Home'],
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/home' => Http::response([
            'slug' => 'home',
            'title' => 'Home',
            'content' => "---\ntitle: YAML Title\n---\n\n# H1 Title\n\nContent here.",
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
    $job->handle();

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('YAML Title')
        ->and($homeDoc->content)->not->toContain('---')
        ->and($homeDoc->content)->not->toContain('# H1 Title')
        ->and($homeDoc->content)->toContain('Content here.');
});
