<?php

use App\Contracts\WikiServiceInterface;
use App\Services\GitHubDocsService;
use App\Services\GitHubService;
use App\Services\WikiServiceFactory;

test('creates GitHubService for github.com URLs', function () {
    $factory = new WikiServiceFactory;

    $service = $factory->make('https://github.com/owner/repo/wiki', 'test-token');

    expect($service)->toBeInstanceOf(GitHubService::class)
        ->and($service)->toBeInstanceOf(WikiServiceInterface::class);
});

test('creates GitHubService for raw.githubusercontent.com URLs', function () {
    $factory = new WikiServiceFactory;

    $service = $factory->make('https://raw.githubusercontent.com/owner/repo/main/CHANGELOG.md', 'test-token');

    expect($service)->toBeInstanceOf(GitHubService::class);
});

test('make() rejects non-GitHub URLs', function () {
    $factory = new WikiServiceFactory;

    $factory->make('https://gitlab.com/group/project/-/wikis', 'test-token');
})->throws(Exception::class, 'Only GitHub wiki and repository URLs are supported');

test('make() rejects unknown hosts', function () {
    $factory = new WikiServiceFactory;

    $factory->make('https://bitbucket.org/owner/repo', 'test-token');
})->throws(Exception::class, 'Only GitHub wiki and repository URLs are supported');

test('makeDocsService creates GitHubDocsService for github.com URLs', function () {
    $factory = new WikiServiceFactory;

    $service = $factory->makeDocsService('https://github.com/owner/repo', 'test-token');

    expect($service)->toBeInstanceOf(GitHubDocsService::class)
        ->and($service)->toBeInstanceOf(WikiServiceInterface::class);
});

test('makeDocsService rejects non-GitHub URLs', function () {
    $factory = new WikiServiceFactory;

    $factory->makeDocsService('https://gitlab.com/group/project', 'test-token');
})->throws(Exception::class, 'Only GitHub wiki and repository URLs are supported');
