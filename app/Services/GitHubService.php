<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubService
{
    public function __construct(
        private string $token,
        private string $baseUrl = 'https://api.github.com'
    ) {}

    /**
     * Get all wiki pages for a repository
     *
     * GitHub wikis are Git repositories accessible via the repos API.
     * This clones the wiki repo contents listing.
     *
     * @param  string  $wikiUrl  The URL to the GitHub wiki (e.g., https://github.com/owner/repo/wiki)
     * @return array<int, array{slug: string, title: string}>
     *
     * @throws \Exception
     */
    public function getWikiPages(string $wikiUrl): array
    {
        $repoPath = $this->extractRepoPath($wikiUrl);

        $response = $this->request("repos/{$repoPath}/wiki/pages");

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch wiki pages: {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Get a specific wiki page content
     *
     * @param  string  $wikiUrl  The URL to the GitHub wiki
     * @param  string  $slug  The wiki page slug
     *
     * @throws \Exception
     */
    public function getWikiPage(string $wikiUrl, string $slug): array
    {
        $repoPath = $this->extractRepoPath($wikiUrl);
        $encodedSlug = urlencode($slug);

        $response = $this->request("repos/{$repoPath}/wiki/pages/{$encodedSlug}");

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch wiki page '{$slug}': {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Get raw file content from a GitHub repository
     *
     * @param  string  $fileUrl  The URL to the GitHub file (e.g., https://github.com/owner/repo/blob/main/CHANGELOG.md)
     *
     * @throws \Exception
     */
    public function getFileContent(string $fileUrl): string
    {
        [$repoPath, $filePath, $ref] = $this->extractFilePathFromUrl($fileUrl);
        $encodedFilePath = collect(explode('/', $filePath))
            ->map(fn (string $segment) => rawurlencode($segment))
            ->implode('/');

        $response = $this->request("repos/{$repoPath}/contents/{$encodedFilePath}?ref=".rawurlencode($ref), [
            'Accept' => 'application/vnd.github.raw+json',
        ]);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch file content from '{$fileUrl}': {$response->body()}");
        }

        return $response->body();
    }

    /**
     * Extract owner/repo from a GitHub URL
     *
     * @throws \Exception
     */
    protected function extractRepoPath(string $url): string
    {
        // Example URL formats:
        // https://github.com/owner/repo/wiki
        // https://github.com/owner/repo/wiki/Page-Name
        // https://github.com/owner/repo/blob/main/file.md
        // https://github.com/owner/repo

        $pattern = '/github\.com\/([^\/]+\/[^\/]+)/';
        if (preg_match($pattern, $url, $matches)) {
            // Remove trailing .git, .wiki.git, or /wiki, /blob, etc.
            $repoPath = preg_replace('/\.(wiki\.)?git$/', '', $matches[1]);

            return $repoPath;
        }

        throw new \Exception("Invalid GitHub URL format: {$url}");
    }

    /**
     * Extract repo path, file path, and ref from a GitHub file URL
     *
     * @return array{0: string, 1: string, 2: string} [repoPath, filePath, ref]
     *
     * @throws \Exception
     */
    protected function extractFilePathFromUrl(string $fileUrl): array
    {
        // Example URL formats:
        // https://github.com/owner/repo/blob/main/CHANGELOG.md
        // https://github.com/owner/repo/blob/develop/docs/CHANGELOG.md

        $pattern = '/github\.com\/([^\/]+\/[^\/]+)\/blob\/([^\/]+)\/(.+)/';
        if (preg_match($pattern, $fileUrl, $matches)) {
            return [
                $matches[1], // repo path (owner/repo)
                $matches[3], // file path
                $matches[2], // ref (branch)
            ];
        }

        throw new \Exception("Invalid GitHub file URL format: {$fileUrl}");
    }

    /**
     * Make an authenticated request to the GitHub API with rate limit handling
     *
     * @param  array<string, string>  $additionalHeaders
     *
     * @throws \Exception
     */
    protected function request(string $endpoint, array $additionalHeaders = []): Response
    {
        $headers = array_merge([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ], $additionalHeaders);

        $response = Http::withHeaders($headers)->get("{$this->baseUrl}/{$endpoint}");

        if ($response->status() === 403 && $response->header('X-RateLimit-Remaining') === '0') {
            $resetTime = (int) $response->header('X-RateLimit-Reset');
            $waitSeconds = max(0, $resetTime - time());

            throw new \Exception("GitHub API rate limit exceeded. Resets in {$waitSeconds} seconds.");
        }

        return $response;
    }
}
