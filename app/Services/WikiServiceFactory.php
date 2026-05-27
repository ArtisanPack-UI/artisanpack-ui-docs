<?php

namespace App\Services;

use App\Contracts\WikiServiceInterface;

class WikiServiceFactory
{
    /**
     * Create the appropriate wiki service based on the given URL
     *
     * @throws \Exception
     */
    public function make(string $url, string $token): WikiServiceInterface
    {
        $source = $this->detectSource($url);

        return match ($source) {
            'github' => app()->make(GitHubService::class, ['token' => $token]),
            'gitlab' => app()->make(GitLabWikiService::class, ['token' => $token]),
        };
    }

    /**
     * Create the appropriate documentation service for a repository docs/ directory
     *
     * @throws \Exception
     */
    public function makeDocsService(string $url, string $token): WikiServiceInterface
    {
        $source = $this->detectSource($url);

        return match ($source) {
            'github' => app()->make(GitHubDocsService::class, ['token' => $token]),
            default => throw new \Exception('Documentation import from a docs/ directory is only supported for GitHub repositories.'),
        };
    }

    /**
     * Detect the source platform from a URL by parsing the host
     *
     * @throws \Exception
     */
    public function detectSource(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null || $host === false) {
            throw new \Exception("Unable to detect wiki source from URL: {$url}");
        }

        $host = strtolower($host);
        $host = preg_replace('/^www\./i', '', $host);

        if (in_array($host, ['github.com', 'raw.githubusercontent.com'], true)) {
            return 'github';
        }

        if ($host === 'gitlab.com') {
            return 'gitlab';
        }

        throw new \Exception("Unable to detect wiki source from URL: {$url}");
    }
}
