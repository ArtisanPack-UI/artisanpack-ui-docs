<?php

namespace App\Services;

use App\Contracts\WikiServiceInterface;

class WikiServiceFactory
{
    /**
     * Create the wiki service for a GitHub wiki URL.
     *
     * @throws \Exception
     */
    public function make(string $url, string $token): WikiServiceInterface
    {
        $this->assertGitHubUrl($url);

        return app()->make(GitHubService::class, ['token' => $token]);
    }

    /**
     * Create the documentation service for a GitHub repository docs/ directory.
     *
     * @throws \Exception
     */
    public function makeDocsService(string $url, string $token): WikiServiceInterface
    {
        $this->assertGitHubUrl($url);

        return app()->make(GitHubDocsService::class, ['token' => $token]);
    }

    /**
     * @throws \Exception
     */
    protected function assertGitHubUrl(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null || $host === false) {
            throw new \Exception("Unable to detect wiki source from URL: {$url}");
        }

        $host = strtolower($host);
        $host = preg_replace('/^www\./i', '', $host);

        if (! in_array($host, ['github.com', 'raw.githubusercontent.com'], true)) {
            throw new \Exception("Only GitHub wiki and repository URLs are supported. Got: {$url}");
        }
    }
}
