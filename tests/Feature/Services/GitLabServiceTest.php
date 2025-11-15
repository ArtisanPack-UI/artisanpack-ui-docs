<?php

use App\Services\GitLabService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new GitLabService('test-token');
});

test('getWikiPages fetches wiki pages successfully', function () {
    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response([
            ['slug' => 'home', 'title' => 'Home'],
            ['slug' => 'installation', 'title' => 'Installation'],
        ], 200),
    ]);

    $result = $this->service->getWikiPages('https://gitlab.com/group/project/-/wikis');

    expect($result)->toBeArray()
        ->toHaveCount(2)
        ->and($result[0]['slug'])->toBe('home')
        ->and($result[1]['slug'])->toBe('installation');
});

test('getWikiPages throws exception on failure', function () {
    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis' => Http::response(['error' => 'Not found'], 404),
    ]);

    $this->service->getWikiPages('https://gitlab.com/group/project/-/wikis');
})->throws(\Exception::class, 'Failed to fetch wiki pages');

test('getWikiPage fetches specific wiki page successfully', function () {
    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/home' => Http::response([
            'slug' => 'home',
            'title' => 'Home',
            'content' => '# Welcome',
        ], 200),
    ]);

    $result = $this->service->getWikiPage('https://gitlab.com/group/project/-/wikis', 'home');

    expect($result)->toBeArray()
        ->and($result['slug'])->toBe('home')
        ->and($result['content'])->toBe('# Welcome');
});

test('getWikiPage throws exception on failure', function () {
    Http::fake([
        'https://gitlab.com/api/v4/projects/group%2Fproject/wikis/nonexistent' => Http::response(['error' => 'Not found'], 404),
    ]);

    $this->service->getWikiPage('https://gitlab.com/group/project/-/wikis', 'nonexistent');
})->throws(\Exception::class, "Failed to fetch wiki page 'nonexistent'");

test('extractProjectPath parses GitLab wiki URL correctly', function () {
    $service = new GitLabService('test-token');
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('extractProjectPath');
    $method->setAccessible(true);

    expect($method->invoke($service, 'https://gitlab.com/group/project/-/wikis'))->toBe('group/project')
        ->and($method->invoke($service, 'https://gitlab.com/group/subgroup/project/-/wikis'))->toBe('group/subgroup/project')
        ->and($method->invoke($service, 'https://gitlab.com/group/project.wiki.git'))->toBe('group/project')
        ->and($method->invoke($service, 'https://gitlab.com/group/subgroup/project.wiki.git'))->toBe('group/subgroup/project');
});

test('extractProjectPath throws exception for invalid URL', function () {
    $service = new GitLabService('test-token');
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('extractProjectPath');
    $method->setAccessible(true);

    $method->invoke($service, 'https://invalid-url.com/something');
})->throws(\Exception::class, 'Invalid GitLab wiki URL format');
