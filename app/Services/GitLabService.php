<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitLabService
{
    public function __construct(
        private string $token,
        private string $baseUrl = 'https://gitlab.com/api/v4'
    ) {}

    /**
     * Get all wiki pages for a project
     *
     * @param  string  $wikiUrl  The URL to the GitLab wiki (e.g., https://gitlab.com/group/project/-/wikis)
     *
     * @throws \Exception
     */
    public function getWikiPages(string $wikiUrl): array
    {
        $projectPath = $this->extractProjectPath($wikiUrl);
        $encodedPath = urlencode($projectPath);

        $response = Http::withHeaders([
            'PRIVATE-TOKEN' => $this->token,
        ])->get("{$this->baseUrl}/projects/{$encodedPath}/wikis");

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch wiki pages: {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Get a specific wiki page content
     *
     * @param  string  $wikiUrl  The URL to the GitLab wiki
     * @param  string  $slug  The wiki page slug
     *
     * @throws \Exception
     */
    public function getWikiPage(string $wikiUrl, string $slug): array
    {
        $projectPath = $this->extractProjectPath($wikiUrl);
        $encodedPath = urlencode($projectPath);
        $encodedSlug = urlencode($slug);

        $response = Http::withHeaders([
            'PRIVATE-TOKEN' => $this->token,
        ])->get("{$this->baseUrl}/projects/{$encodedPath}/wikis/{$encodedSlug}");

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch wiki page '{$slug}': {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Extract project path from wiki URL
     *
     * @throws \Exception
     */
    protected function extractProjectPath(string $wikiUrl): string
    {
        // Example URL formats:
        // https://gitlab.com/group/subgroup/project/-/wikis
        // https://gitlab.com/group/project/-/wikis
        // https://gitlab.com/group/subgroup/project.wiki.git
        // https://gitlab.com/group/project.wiki.git
        // We need to extract: group/subgroup/project or group/project

        // Pattern 1: /-/wikis format
        $pattern1 = '/gitlab\.com\/([^\/]+(?:\/[^\/]+)*)\/-\/wikis/';
        if (preg_match($pattern1, $wikiUrl, $matches)) {
            return $matches[1];
        }

        // Pattern 2: .wiki.git format
        $pattern2 = '/gitlab\.com\/([^\/]+(?:\/[^\/]+)*)\.wiki\.git/';
        if (preg_match($pattern2, $wikiUrl, $matches)) {
            return $matches[1];
        }

        throw new \Exception("Invalid GitLab wiki URL format: {$wikiUrl}");
    }

    /**
     * Get raw file content from a GitLab repository
     *
     * @param  string  $fileUrl  The URL to the GitLab file (e.g., https://gitlab.com/group/project/-/blob/main/CHANGELOG.md)
     *
     * @throws \Exception
     */
    public function getFileContent(string $fileUrl): string
    {
        [$projectPath, $filePath, $ref] = $this->extractFilePathFromUrl($fileUrl);
        $encodedPath = urlencode($projectPath);
        $encodedFilePath = urlencode($filePath);

        $response = Http::withHeaders([
            'PRIVATE-TOKEN' => $this->token,
        ])->get("{$this->baseUrl}/projects/{$encodedPath}/repository/files/{$encodedFilePath}/raw?ref={$ref}");

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch file content from '{$fileUrl}': {$response->body()}");
        }

        return $response->body();
    }

    /**
     * Extract project path, file path, and ref from file URL
     *
     * @return array{0: string, 1: string, 2: string} [projectPath, filePath, ref]
     *
     * @throws \Exception
     */
    protected function extractFilePathFromUrl(string $fileUrl): array
    {
        // Example URL formats:
        // https://gitlab.com/group/project/-/blob/main/CHANGELOG.md
        // https://gitlab.com/group/subgroup/project/-/blob/develop/docs/CHANGELOG.md
        // We need to extract:
        // - project path: group/project or group/subgroup/project
        // - file path: CHANGELOG.md or docs/CHANGELOG.md
        // - ref: main, develop, etc.

        $pattern = '/gitlab\.com\/([^\/]+(?:\/[^\/]+)*)\/-\/blob\/([^\/]+)\/(.+)/';
        if (preg_match($pattern, $fileUrl, $matches)) {
            return [
                $matches[1], // project path
                $matches[3], // file path
                $matches[2], // ref (branch)
            ];
        }

        throw new \Exception("Invalid GitLab file URL format: {$fileUrl}");
    }
}
