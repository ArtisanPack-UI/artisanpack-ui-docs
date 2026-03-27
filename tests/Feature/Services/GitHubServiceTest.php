<?php

use App\Services\GitHubService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new GitHubService('test-token');
});

test('getWikiPages fetches wiki pages successfully', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/wiki/pages' => Http::response([
            ['slug' => 'home', 'title' => 'Home'],
            ['slug' => 'installation', 'title' => 'Installation'],
        ], 200),
    ]);

    $result = $this->service->getWikiPages('https://github.com/owner/repo/wiki');

    expect($result)->toBeArray()
        ->toHaveCount(2)
        ->and($result[0]['slug'])->toBe('home')
        ->and($result[1]['slug'])->toBe('installation');
});

test('getWikiPages throws exception on failure', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/wiki/pages' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->service->getWikiPages('https://github.com/owner/repo/wiki');
})->throws(\Exception::class, 'Failed to fetch wiki pages');

test('getWikiPage fetches specific wiki page successfully', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/wiki/pages/home' => Http::response([
            'slug' => 'home',
            'title' => 'Home',
            'content' => '# Welcome',
        ], 200),
    ]);

    $result = $this->service->getWikiPage('https://github.com/owner/repo/wiki', 'home');

    expect($result)->toBeArray()
        ->and($result['slug'])->toBe('home')
        ->and($result['content'])->toBe('# Welcome');
});

test('getWikiPage throws exception on failure', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/wiki/pages/nonexistent' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->service->getWikiPage('https://github.com/owner/repo/wiki', 'nonexistent');
})->throws(\Exception::class, "Failed to fetch wiki page 'nonexistent'");

test('getFileContent fetches raw file content successfully', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/contents/CHANGELOG.md?ref=main' => Http::response('# Changelog content', 200),
    ]);

    $result = $this->service->getFileContent('https://github.com/owner/repo/blob/main/CHANGELOG.md');

    expect($result)->toBe('# Changelog content');
});

test('getFileContent fetches nested file content successfully', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/contents/docs/CHANGELOG.md?ref=develop' => Http::response('# Nested changelog', 200),
    ]);

    $result = $this->service->getFileContent('https://github.com/owner/repo/blob/develop/docs/CHANGELOG.md');

    expect($result)->toBe('# Nested changelog');
});

test('getFileContent throws exception on failure', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/contents/MISSING.md?ref=main' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->service->getFileContent('https://github.com/owner/repo/blob/main/MISSING.md');
})->throws(\Exception::class, 'Failed to fetch file content');

test('extractRepoPath parses GitHub URLs correctly', function () {
    $service = new GitHubService('test-token');
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('extractRepoPath');
    $method->setAccessible(true);

    expect($method->invoke($service, 'https://github.com/owner/repo/wiki'))->toBe('owner/repo')
        ->and($method->invoke($service, 'https://github.com/owner/repo/wiki/Page-Name'))->toBe('owner/repo')
        ->and($method->invoke($service, 'https://github.com/owner/repo/blob/main/file.md'))->toBe('owner/repo')
        ->and($method->invoke($service, 'https://github.com/owner/repo'))->toBe('owner/repo')
        ->and($method->invoke($service, 'https://github.com/owner/repo.git'))->toBe('owner/repo')
        ->and($method->invoke($service, 'https://github.com/owner/repo.wiki.git'))->toBe('owner/repo');
});

test('extractRepoPath throws exception for invalid URL', function () {
    $service = new GitHubService('test-token');
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('extractRepoPath');
    $method->setAccessible(true);

    $method->invoke($service, 'https://invalid-url.com/something');
})->throws(\Exception::class, 'Invalid GitHub URL format');

test('extractFilePathFromUrl parses GitHub file URLs correctly', function () {
    $service = new GitHubService('test-token');
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('extractFilePathFromUrl');
    $method->setAccessible(true);

    $result = $method->invoke($service, 'https://github.com/owner/repo/blob/main/CHANGELOG.md');
    expect($result)->toBe(['owner/repo', 'CHANGELOG.md', 'main']);

    $result = $method->invoke($service, 'https://github.com/owner/repo/blob/develop/docs/CHANGELOG.md');
    expect($result)->toBe(['owner/repo', 'docs/CHANGELOG.md', 'develop']);
});

test('extractFilePathFromUrl throws exception for invalid URL', function () {
    $service = new GitHubService('test-token');
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('extractFilePathFromUrl');
    $method->setAccessible(true);

    $method->invoke($service, 'https://github.com/owner/repo/tree/main');
})->throws(\Exception::class, 'Invalid GitHub file URL format');

test('request handles rate limiting', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/wiki/pages' => Http::response(
            ['message' => 'API rate limit exceeded'],
            403,
            ['X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => (string) (time() + 60)]
        ),
    ]);

    $this->service->getWikiPages('https://github.com/owner/repo/wiki');
})->throws(\Exception::class, 'GitHub API rate limit exceeded');

test('request sends correct authentication headers', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/wiki/pages' => Http::response([], 200),
    ]);

    $this->service->getWikiPages('https://github.com/owner/repo/wiki');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->hasHeader('Accept', 'application/vnd.github+json')
            && $request->hasHeader('X-GitHub-Api-Version', '2022-11-28');
    });
});
