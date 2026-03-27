<?php

use App\Contracts\WikiServiceInterface;
use App\Services\GitHubService;
use App\Services\GitLabWikiService;
use App\Services\WikiServiceFactory;

test('creates GitHubService for github.com URLs', function () {
    $factory = new WikiServiceFactory;

    $service = $factory->make('https://github.com/owner/repo/wiki', 'test-token');

    expect($service)->toBeInstanceOf(GitHubService::class)
        ->and($service)->toBeInstanceOf(WikiServiceInterface::class);
});

test('creates GitLabWikiService for gitlab.com URLs', function () {
    $factory = new WikiServiceFactory;

    $service = $factory->make('https://gitlab.com/group/project/-/wikis', 'test-token');

    expect($service)->toBeInstanceOf(GitLabWikiService::class)
        ->and($service)->toBeInstanceOf(WikiServiceInterface::class);
});

test('creates GitHubService for raw.githubusercontent.com URLs', function () {
    $factory = new WikiServiceFactory;

    $service = $factory->make('https://raw.githubusercontent.com/owner/repo/main/CHANGELOG.md', 'test-token');

    expect($service)->toBeInstanceOf(GitHubService::class);
});

test('throws exception for unsupported URLs', function () {
    $factory = new WikiServiceFactory;

    $factory->make('https://bitbucket.org/owner/repo', 'test-token');
})->throws(\Exception::class, 'Unable to detect wiki source from URL');

test('detectSource returns github for github.com URLs', function () {
    $factory = new WikiServiceFactory;

    expect($factory->detectSource('https://github.com/owner/repo/wiki'))->toBe('github')
        ->and($factory->detectSource('https://github.com/owner/repo/blob/main/CHANGELOG.md'))->toBe('github')
        ->and($factory->detectSource('https://raw.githubusercontent.com/owner/repo/main/file.md'))->toBe('github');
});

test('detectSource returns gitlab for gitlab.com URLs', function () {
    $factory = new WikiServiceFactory;

    expect($factory->detectSource('https://gitlab.com/group/project/-/wikis'))->toBe('gitlab')
        ->and($factory->detectSource('https://gitlab.com/group/project/-/blob/main/CHANGELOG.md'))->toBe('gitlab');
});

test('detectSource throws exception for unknown URLs', function () {
    $factory = new WikiServiceFactory;

    $factory->detectSource('https://bitbucket.org/owner/repo');
})->throws(\Exception::class, 'Unable to detect wiki source from URL');
