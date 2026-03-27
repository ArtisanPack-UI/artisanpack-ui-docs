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
     * Detect the source platform from a URL
     *
     * @throws \Exception
     */
    public function detectSource(string $url): string
    {
        if (str_contains($url, 'github.com') || str_contains($url, 'raw.githubusercontent.com')) {
            return 'github';
        }

        if (str_contains($url, 'gitlab.com')) {
            return 'gitlab';
        }

        throw new \Exception("Unable to detect wiki source from URL: {$url}");
    }
}
