<?php

use App\Contracts\WikiServiceInterface;
use App\Jobs\ImportWikiDocumentation;
use App\Services\WikiServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Modules\Core\Setting;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

beforeEach(function () {
    Log::shouldReceive('info')->zeroOrMoreTimes();
    Log::shouldReceive('error')->zeroOrMoreTimes();

    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('test-token'),
    ]);
});

function mockDocsPages(array $pages): void
{
    $mock = Mockery::mock(WikiServiceInterface::class);
    $mock->shouldReceive('getWikiPagesWithContent')->andReturn($pages);

    $factory = Mockery::mock(WikiServiceFactory::class);
    $factory->shouldReceive('detectSource')->andReturn('github');
    $factory->shouldReceive('makeDocsService')->andReturn($mock);
    $factory->shouldReceive('make')->andReturn($mock);
    app()->bind(WikiServiceFactory::class, fn () => $factory);
}

test('docs_url takes priority over wiki_url as the import source', function () {
    $package = Package::factory()->create([
        'slug' => 'core',
        'wiki_url' => 'https://github.com/owner/repo/wiki',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    $factory = Mockery::mock(WikiServiceFactory::class);
    $factory->shouldReceive('detectSource')->andReturn('github');

    $docsService = Mockery::mock(WikiServiceInterface::class);
    $docsService->shouldReceive('getWikiPagesWithContent')
        ->with('https://github.com/owner/repo')
        ->once()
        ->andReturn([['slug' => 'home', 'title' => 'Home', 'content' => '# Home']]);

    $factory->shouldReceive('makeDocsService')->once()->andReturn($docsService);
    $factory->shouldReceive('make')->never();
    app()->bind(WikiServiceFactory::class, fn () => $factory);

    (new ImportWikiDocumentation($package))->handle();

    expect(Documentation::where('slug', 'home')->exists())->toBeTrue();
});

test('falls back to wiki_url when docs_url is not set', function () {
    $package = Package::factory()->create([
        'wiki_url' => 'https://github.com/owner/repo/wiki',
        'docs_url' => null,
    ]);

    $factory = Mockery::mock(WikiServiceFactory::class);
    $factory->shouldReceive('detectSource')->andReturn('github');

    $wikiService = Mockery::mock(WikiServiceInterface::class);
    $wikiService->shouldReceive('getWikiPagesWithContent')
        ->with('https://github.com/owner/repo/wiki')
        ->once()
        ->andReturn([['slug' => 'home', 'title' => 'Home', 'content' => '# Home']]);

    $factory->shouldReceive('make')->once()->andReturn($wikiService);
    $factory->shouldReceive('makeDocsService')->never();
    app()->bind(WikiServiceFactory::class, fn () => $factory);

    (new ImportWikiDocumentation($package))->handle();

    expect(Documentation::where('slug', 'home')->exists())->toBeTrue();
});

test('parses menu_order, parent and meta_description from front matter', function () {
    $package = Package::factory()->create([
        'slug' => 'forms',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    mockDocsPages([
        ['slug' => 'advanced', 'title' => 'Advanced', 'content' => "---\ntitle: Advanced Topics\nmenu_order: 5\nmeta_description: Custom description for advanced topics.\n---\n# Advanced"],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    $doc = Documentation::where('slug', 'advanced')->first();

    expect($doc->title)->toBe('Advanced Topics')
        ->and($doc->menu_order)->toBe(5)
        ->and($doc->meta_description)->toBe('Custom description for advanced topics.');
});

test('preserves existing menu_order on re-import when front matter omits it', function () {
    $package = Package::factory()->create([
        'slug' => 'forms',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    Documentation::create([
        'slug' => 'usage',
        'title' => 'Usage',
        'content' => 'old',
        'menu_order' => 9,
        'package_id' => $package->id,
    ]);

    mockDocsPages([
        ['slug' => 'usage', 'title' => 'Usage', 'content' => "# Usage\n\nNew content."],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    expect(Documentation::where('slug', 'usage')->first()->menu_order)->toBe(9);
});

test('respects front matter parent override', function () {
    $package = Package::factory()->create([
        'slug' => 'forms',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    mockDocsPages([
        ['slug' => 'reference', 'title' => 'Reference', 'content' => '# Reference'],
        ['slug' => 'standalone', 'title' => 'Standalone', 'content' => "---\nparent: reference\n---\n# Standalone"],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    $reference = Documentation::where('slug', 'reference')->first();
    $standalone = Documentation::where('slug', 'standalone')->first();

    expect($standalone->parent)->toBe($reference->id);
});

test('infers parent from immediate directory for nested slugs', function () {
    $package = Package::factory()->create([
        'slug' => 'forms',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    mockDocsPages([
        ['slug' => 'advanced', 'title' => 'Advanced', 'content' => '# Advanced'],
        ['slug' => 'advanced/webhooks', 'title' => 'Webhooks', 'content' => '# Webhooks'],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    $parent = Documentation::where('slug', 'advanced')->first();
    $child = Documentation::where('slug', 'advanced/webhooks')->first();

    expect($child->parent)->toBe($parent->id)
        ->and($parent->parent)->toBeNull();
});

test('translates flat wiki-name links to hierarchical docs slugs', function () {
    config(['app.url' => 'https://docs.artisanpackui.dev']);

    $package = Package::factory()->create([
        'slug' => 'forms',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    mockDocsPages([
        ['slug' => 'advanced', 'title' => 'Advanced', 'content' => "# Advanced\n\nSee [Webhooks](Advanced-Webhooks) and [REST API](Api-Rest-Api)."],
        ['slug' => 'advanced/webhooks', 'title' => 'Webhooks', 'content' => '# Webhooks'],
        ['slug' => 'api/rest-api', 'title' => 'REST API', 'content' => '# REST API'],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    $advanced = Documentation::where('slug', 'advanced')->first();

    expect($advanced->content)
        ->toContain('https://docs.artisanpackui.dev/documentation/forms/advanced/webhooks')
        ->toContain('https://docs.artisanpackui.dev/documentation/forms/api/rest-api');
});

test('resolves relative sibling links within a section', function () {
    config(['app.url' => 'https://docs.artisanpackui.dev']);

    $package = Package::factory()->create([
        'slug' => 'forms',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    mockDocsPages([
        ['slug' => 'advanced/webhooks', 'title' => 'Webhooks', 'content' => "# Webhooks\n\nAlso see [Spam Protection](spam-protection.md)."],
        ['slug' => 'advanced/spam-protection', 'title' => 'Spam Protection', 'content' => '# Spam Protection'],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    $webhooks = Documentation::where('slug', 'advanced/webhooks')->first();

    expect($webhooks->content)
        ->toContain('https://docs.artisanpackui.dev/documentation/forms/advanced/spam-protection');
});

test('translates wikilinks and preserves anchors', function () {
    config(['app.url' => 'https://docs.artisanpackui.dev']);

    $package = Package::factory()->create([
        'slug' => 'hooks',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    mockDocsPages([
        ['slug' => 'home', 'title' => 'Home', 'content' => "# Home\n\nStart with [[Getting Started]]."],
        ['slug' => 'getting-started', 'title' => 'Getting Started', 'content' => '# Getting Started'],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    $home = Documentation::where('slug', 'home')->first();

    expect($home->content)
        ->toContain('[Getting Started](https://docs.artisanpackui.dev/documentation/hooks/getting-started)');
});

test('leaves external links unchanged', function () {
    config(['app.url' => 'https://docs.artisanpackui.dev']);

    $package = Package::factory()->create([
        'slug' => 'forms',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    mockDocsPages([
        ['slug' => 'home', 'title' => 'Home', 'content' => "# Home\n\nVisit [Laravel](https://laravel.com)."],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    expect(Documentation::where('slug', 'home')->first()->content)
        ->toContain('[Laravel](https://laravel.com)');
});

test('removes stale docs pages on re-import', function () {
    $package = Package::factory()->create([
        'slug' => 'forms',
        'docs_url' => 'https://github.com/owner/repo',
    ]);

    Documentation::create([
        'slug' => 'old-page',
        'title' => 'Old',
        'content' => 'gone',
        'package_id' => $package->id,
    ]);

    mockDocsPages([
        ['slug' => 'home', 'title' => 'Home', 'content' => '# Home'],
    ]);

    (new ImportWikiDocumentation($package))->handle();

    expect(Documentation::where('slug', 'old-page')->exists())->toBeFalse()
        ->and(Documentation::where('slug', 'home')->exists())->toBeTrue();
});
