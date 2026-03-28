<?php

use App\Services\GitHubService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new GitHubService('test-token');
    $this->tempDir = null;
});

afterEach(function () {
    if ($this->tempDir && is_dir($this->tempDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->tempDir);
    }
});

function createFakeWikiService(string $tempDir): GitHubService
{
    return new class('test-token', $tempDir) extends GitHubService
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
}

test('getWikiPagesWithContent clones wiki and returns pages with content', function () {
    $this->tempDir = sys_get_temp_dir().'/test-wiki-'.uniqid();
    mkdir($this->tempDir, 0777, true);
    file_put_contents("{$this->tempDir}/home.md", "# Welcome\n\nHome content.");
    file_put_contents("{$this->tempDir}/installation.md", "# Installation\n\nInstall steps.");

    $service = createFakeWikiService($this->tempDir);
    $result = $service->getWikiPagesWithContent('https://github.com/owner/repo/wiki');

    expect($result)->toBeArray()
        ->toHaveCount(2);

    $slugs = array_column($result, 'slug');
    expect($slugs)->toContain('home')
        ->toContain('installation');

    $homePage = collect($result)->firstWhere('slug', 'home');
    expect($homePage['content'])->toBe("# Welcome\n\nHome content.");
});

test('getWikiPagesWithContent handles subdirectories', function () {
    $this->tempDir = sys_get_temp_dir().'/test-wiki-'.uniqid();
    mkdir("{$this->tempDir}/guides", 0777, true);
    file_put_contents("{$this->tempDir}/guides.md", '# Guides');
    file_put_contents("{$this->tempDir}/guides/usage.md", '# Usage Guide');

    $service = createFakeWikiService($this->tempDir);
    $result = $service->getWikiPagesWithContent('https://github.com/owner/repo/wiki');

    $slugs = array_column($result, 'slug');
    expect($slugs)->toContain('guides')
        ->toContain('guides/usage');

    $childPage = collect($result)->firstWhere('slug', 'guides/usage');
    expect($childPage['content'])->toBe('# Usage Guide');
});

test('getWikiPagesWithContent deduplicates dir/dir pages when dir exists at root', function () {
    $this->tempDir = sys_get_temp_dir().'/test-wiki-'.uniqid();
    mkdir("{$this->tempDir}/installation", 0777, true);
    file_put_contents("{$this->tempDir}/installation.md", '# Installation Overview');
    file_put_contents("{$this->tempDir}/installation/installation.md", '# Installation Overview (duplicate)');
    file_put_contents("{$this->tempDir}/installation/configuration.md", '# Configuration');
    file_put_contents("{$this->tempDir}/installation/requirements.md", '# Requirements');

    $service = createFakeWikiService($this->tempDir);
    $result = $service->getWikiPagesWithContent('https://github.com/owner/repo/wiki');

    $slugs = array_column($result, 'slug');
    expect($slugs)->toContain('installation')
        ->toContain('installation/configuration')
        ->toContain('installation/requirements')
        ->not->toContain('installation/installation');
});

test('readWikiPage rejects symlinks pointing outside clone directory', function () {
    $this->tempDir = sys_get_temp_dir().'/test-wiki-'.uniqid();
    mkdir($this->tempDir, 0777, true);

    // Create a file outside the clone directory
    $outsideDir = sys_get_temp_dir().'/test-wiki-outside-'.uniqid();
    mkdir($outsideDir, 0777, true);
    file_put_contents("{$outsideDir}/secret.md", 'SECRET DATA');

    // Create a symlink inside the clone pointing to the outside file
    symlink("{$outsideDir}/secret.md", "{$this->tempDir}/evil.md");

    $service = createFakeWikiService($this->tempDir);

    // The symlinked page resolves outside the clone — readWikiPage should throw
    expect(fn () => $service->getWikiPagesWithContent('https://github.com/owner/repo/wiki'))
        ->toThrow(\Exception::class, 'not found or path is outside repository');

    // Clean up outside dir
    unlink("{$outsideDir}/secret.md");
    rmdir($outsideDir);
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

test('getFileContent handles raw.githubusercontent.com URLs', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/contents/CHANGELOG.md?ref=main' => Http::response('# Raw changelog', 200),
    ]);

    $result = $this->service->getFileContent('https://raw.githubusercontent.com/owner/repo/main/CHANGELOG.md');

    expect($result)->toBe('# Raw changelog');
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

test('getFileContent throws exception on server error', function () {
    Http::fake([
        'https://api.github.com/repos/owner/repo/contents/CHANGELOG.md?ref=main' => Http::response(['message' => 'Internal Server Error'], 500),
    ]);

    $this->service->getFileContent('https://github.com/owner/repo/blob/main/CHANGELOG.md');
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

    $result = $method->invoke($service, 'https://raw.githubusercontent.com/owner/repo/main/CHANGELOG.md');
    expect($result)->toBe(['owner/repo', 'CHANGELOG.md', 'main']);

    $result = $method->invoke($service, 'https://raw.githubusercontent.com/owner/repo/develop/docs/CHANGELOG.md');
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
            && $request->hasHeader('X-GitHub-Api-Version', '2022-11-28')
            && $request->hasHeader('Accept', 'application/vnd.github.raw+json');
    });
});
