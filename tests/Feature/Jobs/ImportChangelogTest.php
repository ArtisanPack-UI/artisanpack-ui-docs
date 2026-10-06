<?php

use App\Contracts\WikiServiceInterface;
use App\Enums\ImportStatus;
use App\Jobs\ImportChangelog;
use App\Services\WikiServiceFactory;
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

function mockGitHubFileContent(string $expectedUrl, string $content): void
{
    $mock = Mockery::mock(WikiServiceInterface::class);
    $mock->shouldReceive('getFileContent')
        ->once()
        ->with($expectedUrl)
        ->andReturn($content);

    $factory = Mockery::mock(WikiServiceFactory::class);
    $factory->shouldReceive('detectSource')->andReturn('github');
    $factory->shouldReceive('make')->andReturn($mock);
    app()->bind(WikiServiceFactory::class, fn () => $factory);
}

test('imports changelog successfully', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $changelogContent = "# Changelog\n\n## [1.0.0] - 2025-01-01\n- Initial release";

    mockGitHubFileContent($package->changelog_url, $changelogContent);

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

    mockGitHubFileContent($package->changelog_url, $newContent);

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

    $mock = Mockery::mock(WikiServiceInterface::class);
    $mock->shouldReceive('getFileContent')
        ->once()
        ->with($package->changelog_url)
        ->andThrow(new Exception('Failed to fetch file content'));

    $factory = Mockery::mock(WikiServiceFactory::class);
    $factory->shouldReceive('detectSource')->andReturn('github');
    $factory->shouldReceive('make')->andReturn($mock);
    app()->bind(WikiServiceFactory::class, fn () => $factory);

    $job = new ImportChangelog($package);
    $job->handle();
})->throws(Exception::class);

test('throws exception when github token is not configured', function () {
    Log::shouldReceive('error')->once();

    Setting::where('key', 'github_token')->delete();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $job = new ImportChangelog($package);
    $job->handle();
})->throws(Exception::class, 'GitHub token not configured or could not be decrypted');

test('throws exception when github token is malformed', function () {
    Log::shouldReceive('error')->once();

    Setting::where('key', 'github_token')->update(['value' => 'not-encrypted']);

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
    ]);

    $job = new ImportChangelog($package);
    $job->handle();
})->throws(Exception::class, 'GitHub token not configured or could not be decrypted');

test('handles changelog in subdirectory', function () {
    Log::shouldReceive('info')->once();

    $package = Package::factory()->create([
        'name' => 'Test Package',
        'changelog_url' => 'https://github.com/owner/repo/blob/develop/docs/CHANGELOG.md',
    ]);

    $changelogContent = "# Changelog\n\n## [1.0.0] - 2025-01-01\n- Initial release";

    mockGitHubFileContent($package->changelog_url, $changelogContent);

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

    mockGitHubFileContent($package->changelog_url, $changelogContent);

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

    mockGitHubFileContent($package->changelog_url, $changelogContent);

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

    mockGitHubFileContent($package->changelog_url, $changelogContent);

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

    mockGitHubFileContent($package->changelog_url, $changelogContent);

    $job = new ImportChangelog($package);
    $job->handle();

    $changelog = Changelog::first();
    expect($changelog->title)->toBe('Digital Shopfront CMS Accessibility Changelog')
        ->and($changelog->content)->not->toContain('# Digital Shopfront CMS Accessibility Changelog')
        ->and($changelog->content)->toContain('## Version 1.0.0')
        ->and($changelog->content)->toContain('- Initial release');
});

test('stamps the changelog import as succeeded with a timestamp', function () {
    Log::shouldReceive('info')->once();
    $this->freezeSecond();

    $package = Package::factory()->create([
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
        'changelog_import_status' => ImportStatus::Failed,
        'changelog_import_error' => 'previous failure',
    ]);

    mockGitHubFileContent($package->changelog_url, "# Changelog\n\n## [1.0.0]");

    (new ImportChangelog($package))->handle();

    $package->refresh();
    expect($package->changelog_import_status)->toBe(ImportStatus::Succeeded)
        ->and($package->changelog_import_error)->toBeNull()
        ->and($package->changelog_imported_at?->equalTo(now()))->toBeTrue()
        ->and($package->docs_import_status)->toBeNull();
});

test('leaves the status queued while a failed attempt may still be retried', function () {
    Log::shouldReceive('error')->once();

    $package = Package::factory()->create([
        'changelog_url' => 'https://github.com/owner/repo/blob/main/CHANGELOG.md',
        'changelog_import_status' => ImportStatus::Queued,
    ]);

    $mock = Mockery::mock(WikiServiceInterface::class);
    $mock->shouldReceive('getFileContent')->andThrow(new Exception('File not found'));
    $factory = Mockery::mock(WikiServiceFactory::class);
    $factory->shouldReceive('make')->andReturn($mock);
    app()->bind(WikiServiceFactory::class, fn () => $factory);

    expect(fn () => (new ImportChangelog($package))->handle())->toThrow(Exception::class, 'File not found');

    expect($package->fresh()->changelog_import_status)->toBe(ImportStatus::Queued);
});

test('the failed hook stamps the changelog import as failed and keeps the last success time', function () {
    $package = Package::factory()->create([
        'changelog_imported_at' => '2026-09-01 00:00:00',
        'changelog_import_status' => ImportStatus::Queued,
    ]);

    (new ImportChangelog($package))->failed(new Exception('File not found'));

    $package->refresh();
    expect($package->changelog_import_status)->toBe(ImportStatus::Failed)
        ->and($package->changelog_import_error)->toBe('File not found')
        ->and($package->changelog_imported_at->toDateTimeString())->toBe('2026-09-01 00:00:00');
});

test('the failed hook falls back to a generic message without an exception', function () {
    $package = Package::factory()->create(['changelog_import_status' => ImportStatus::Queued]);

    (new ImportChangelog($package))->failed(null);

    $package->refresh();
    expect($package->changelog_import_status)->toBe(ImportStatus::Failed)
        ->and($package->changelog_import_error)->toBe('The import job failed.');
});

test('truncates very long import errors', function () {
    $package = Package::factory()->create();

    (new ImportChangelog($package))->failed(new Exception(str_repeat('x', 5000)));

    expect(mb_strlen($package->fresh()->changelog_import_error))
        ->toBe(Package::IMPORT_ERROR_MAX_LENGTH + 3);
});
