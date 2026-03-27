<?php

use App\Services\GitHubService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new GitHubService('test-token');
});

test('getWikiPagesWithContent clones wiki and returns pages with content', function () {
    // Create a mock subclass that overrides cloneWikiRepo to use a temp directory
    $tempDir = sys_get_temp_dir().'/test-wiki-'.uniqid();
    mkdir($tempDir, 0777, true);
    file_put_contents("{$tempDir}/home.md", "# Welcome\n\nHome content.");
    file_put_contents("{$tempDir}/installation.md", "# Installation\n\nInstall steps.");

    $service = new class('test-token', $tempDir) extends GitHubService
    {
        private string $fakePath;

        public function __construct(string $token, string $fakePath)
        {
            parent::__construct($token);
            $this->fakePath = $fakePath;
        }

        protected function cloneWikiRepo(string $wikiUrl): string
        {
            return $this->fakePath;
        }

        protected function removeDirectory(string $path): void
        {
            // Don't remove during test — we'll clean up manually
        }
    };

    $result = $service->getWikiPagesWithContent('https://github.com/owner/repo/wiki');

    expect($result)->toBeArray()
        ->toHaveCount(2);

    $slugs = array_column($result, 'slug');
    expect($slugs)->toContain('home')
        ->toContain('installation');

    $homePage = collect($result)->firstWhere('slug', 'home');
    expect($homePage['content'])->toBe("# Welcome\n\nHome content.");

    // Clean up
    array_map('unlink', glob("{$tempDir}/*.md"));
    rmdir($tempDir);
});

test('getWikiPagesWithContent handles subdirectories', function () {
    $tempDir = sys_get_temp_dir().'/test-wiki-'.uniqid();
    mkdir("{$tempDir}/guides", 0777, true);
    file_put_contents("{$tempDir}/guides.md", '# Guides');
    file_put_contents("{$tempDir}/guides/usage.md", '# Usage Guide');

    $service = new class('test-token', $tempDir) extends GitHubService
    {
        private string $fakePath;

        public function __construct(string $token, string $fakePath)
        {
            parent::__construct($token);
            $this->fakePath = $fakePath;
        }

        protected function cloneWikiRepo(string $wikiUrl): string
        {
            return $this->fakePath;
        }

        protected function removeDirectory(string $path): void {}
    };

    $result = $service->getWikiPagesWithContent('https://github.com/owner/repo/wiki');

    $slugs = array_column($result, 'slug');
    expect($slugs)->toContain('guides')
        ->toContain('guides/usage');

    $childPage = collect($result)->firstWhere('slug', 'guides/usage');
    expect($childPage['content'])->toBe('# Usage Guide');

    // Clean up
    unlink("{$tempDir}/guides.md");
    unlink("{$tempDir}/guides/usage.md");
    rmdir("{$tempDir}/guides");
    rmdir($tempDir);
});

test('getWikiPagesWithContent throws exception when clone fails', function () {
    $service = new class('test-token') extends GitHubService
    {
        protected function cloneWikiRepo(string $wikiUrl): string
        {
            throw new \Exception("Failed to clone wiki repository for 'owner/repo': remote: Repository not found.");
        }
    };

    $service->getWikiPagesWithContent('https://github.com/owner/repo/wiki');
})->throws(\Exception::class, 'Failed to clone wiki repository');

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
        'https://api.github.com/repos/owner/repo/contents/test.md?ref=main' => Http::response(
            ['message' => 'API rate limit exceeded'],
            403,
            ['X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => (string) (time() + 60)]
        ),
    ]);

    $this->service->getFileContent('https://github.com/owner/repo/blob/main/test.md');
})->throws(\Exception::class, 'GitHub API rate limit exceeded');

test('request sends correct authentication headers', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/contents/test.md?ref=main' => Http::response('content', 200),
    ]);

    $this->service->getFileContent('https://github.com/owner/repo/blob/main/test.md');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->hasHeader('X-GitHub-Api-Version', '2022-11-28');
    });
});
