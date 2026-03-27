<?php

use App\Jobs\ImportChangelog;
use App\Services\GitHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Modules\Core\Setting;
use Modules\Packages\Changelog;
use Modules\Packages\Package;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::create([
        'key' => 'github_token',
        'value' => encrypt('test-token'),
    ]);
});

function mockGitHubFileContent(string $content): void
{
    $mock = Mockery::mock(GitHubService::class);
    $mock->shouldReceive('getFileContent')->andReturn($content);
    app()->bind(GitHubService::class, fn () => $mock);
}

test('imports changelog successfully', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $changelogContent = "# Changelog\n\n## [1.0.0] - 2025-01-01\n- Initial release";

    mockGitHubFileContent($changelogContent);

    $job = new ImportChangelog($package);
    $job->handle();

    expect(Changelog::count())->toBe(1);

    $changelog = Changelog::first();
    expect($changelog->title)->toBe('Test Package Changelog')
        ->and($changelog->content)->not->toContain('# Changelog')
        ->and($changelog->content)->toContain('## [1.0.0] - 2025-01-01')
        ->and($changelog->content)->toContain('- Initial release')
        ->and($changelog->package_id)->toBe($package->id);
});

test('updates existing changelog when re-importing', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    Changelog::create([
        'title' => 'Old Title',
        'content' => 'Old content',
        'package_id' => $package->id,
    ]);

    $newContent = "# Changelog\n\n## [2.0.0] - 2025-02-01\n- Updated release";

    mockGitHubFileContent($newContent);

    $job = new ImportChangelog($package);
    $job->handle();

    expect(Changelog::count())->toBe(1);

    $changelog = Changelog::first();
    expect($changelog->title)->toBe('Test Package Changelog')
        ->and($changelog->content)->not->toContain('# Changelog')
        ->and($changelog->content)->toContain('## [2.0.0] - 2025-02-01')
        ->and($changelog->content)->toContain('- Updated release');
});

test('logs error and throws exception on failure', function () {
    Log::shouldReceive('error')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $mock = Mockery::mock(GitHubService::class);
    $mock->shouldReceive('getFileContent')
        ->andThrow(new \Exception('Failed to fetch file content'));
    app()->bind(GitHubService::class, fn () => $mock);

    $job = new ImportChangelog($package);
    $job->handle();
})->throws(\Exception::class);

test('throws exception when github token is not configured', function () {
    Log::shouldReceive('error')->once();

    Setting::where('key', 'github_token')->delete();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $job = new ImportChangelog($package);
    $job->handle();
})->throws(\Exception::class, 'GitHub token not configured');

test('throws exception when github token is malformed', function () {
    Log::shouldReceive('error')->once();

    Setting::where('key', 'github_token')->update(['value' => 'not-encrypted']);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $job = new ImportChangelog($package);
    $job->handle();
})->throws(\Exception::class, 'GitHub token not configured');

test('handles changelog in subdirectory', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/develop/docs/CHANGELOG.md',
    ]);

    $changelogContent = "# Changelog\n\n## [1.0.0] - 2025-01-01\n- Initial release";

    mockGitHubFileContent($changelogContent);

    $job = new ImportChangelog($package);
    $job->handle();

    expect(Changelog::count())->toBe(1);

    $changelog = Changelog::first();
    expect($changelog->title)->toBe('Test Package Changelog')
        ->and($changelog->content)->not->toContain('# Changelog')
        ->and($changelog->content)->toContain('## [1.0.0] - 2025-01-01')
        ->and($changelog->content)->toContain('- Initial release')
        ->and($changelog->package_id)->toBe($package->id);
});

test('removes only first H1 header and preserves rest of content', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $changelogContent = "# Changelog\n\nAll notable changes to this project.\n\n## [2.0.0] - 2025-02-01\n- New feature\n\n## [1.0.0] - 2025-01-01\n- Initial release";

    mockGitHubFileContent($changelogContent);

    $job = new ImportChangelog($package);
    $job->handle();

    $changelog = Changelog::first();
    expect($changelog->content)->not->toContain('# Changelog')
        ->and($changelog->content)->toContain('All notable changes to this project.')
        ->and($changelog->content)->toContain('## [2.0.0] - 2025-02-01')
        ->and($changelog->content)->toContain('## [1.0.0] - 2025-01-01');
});

test('handles changelog without H1 header', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $changelogContent = "## [1.0.0] - 2025-01-01\n- Initial release";

    mockGitHubFileContent($changelogContent);

    $job = new ImportChangelog($package);
    $job->handle();

    $changelog = Changelog::first();
    expect($changelog->content)->toBe($changelogContent);
});

test('removes H1 header with leading whitespace', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $changelogContent = "  # Changelog\n\n## [1.0.0] - 2025-01-01\n- Initial release";

    mockGitHubFileContent($changelogContent);

    $job = new ImportChangelog($package);
    $job->handle();

    $changelog = Changelog::first();
    expect($changelog->content)->not->toContain('# Changelog')
        ->and($changelog->content)->toContain('## [1.0.0] - 2025-01-01')
        ->and($changelog->content)->toContain('- Initial release');
});

test('removes long H1 header like Digital Shopfront CMS Accessibility Changelog', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Digital Shopfront CMS Accessibility',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $changelogContent = "# Digital Shopfront CMS Accessibility Changelog\n\n## Version 1.0.0\n- Initial release";

    mockGitHubFileContent($changelogContent);

    $job = new ImportChangelog($package);
    $job->handle();

    $changelog = Changelog::first();
    expect($changelog->title)->toBe('Digital Shopfront CMS Accessibility Changelog')
        ->and($changelog->content)->not->toContain('# Digital Shopfront CMS Accessibility Changelog')
        ->and($changelog->content)->toContain('## Version 1.0.0')
        ->and($changelog->content)->toContain('- Initial release');
});
