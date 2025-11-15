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
            'content' => '# Welcome to the documentation',
        ], 200),
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/installation' => Http::response([
            'slug' => 'installation',
            'title' => 'Installation',
            'content' => '# Installation Guide',
        ], 200),
    ]);

    $job = new ImportWikiDocumentation($package, 'test-token');
    $job->handle();

    expect(Documentation::count())->toBe(2);

    $homeDoc = Documentation::where('slug', 'home')->first();
    expect($homeDoc->title)->toBe('Home')
        ->and($homeDoc->content)->toBe('# Welcome to the documentation')
        ->and($homeDoc->package_id)->toBe($package->id);

    $installDoc = Documentation::where('slug', 'installation')->first();
    expect($installDoc->title)->toBe('Installation')
        ->and($installDoc->content)->toBe('# Installation Guide')
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
