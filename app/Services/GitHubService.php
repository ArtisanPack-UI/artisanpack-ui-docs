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
     * Get all wiki pages with their content by cloning the wiki Git repo once
     *
     * GitHub wikis are separate Git repositories (repo.wiki.git) that are not
     * accessible via the REST API. This clones the wiki repo to a temp directory,
     * parses the markdown files, and returns all pages with content in a single operation.
     *
     * @param  string  $wikiUrl  The URL to the GitHub wiki (e.g., https://github.com/owner/repo/wiki)
     * @return array<int, array{slug: string, title: string, content: string}>
     *
     * @throws \Exception
     */
    public function getWikiPagesWithContent(string $wikiUrl): array
    {
        $clonePath = $this->cloneWikiRepo($wikiUrl);

        try {
            $pages = $this->parseWikiDirectory($clonePath);

            return array_map(function (array $page) use ($clonePath) {
                $pageData = $this->readWikiPage($clonePath, $page['slug']);

                return array_merge($page, ['content' => $pageData['content']]);
            }, $pages);
        } finally {
            $this->removeDirectory($clonePath);
        }
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
     * Clone the wiki Git repository to a temporary directory
     *
     * @throws \Exception
     */
    protected function cloneWikiRepo(string $wikiUrl): string
    {
        $repoPath = $this->extractRepoPath($wikiUrl);
        $clonePath = sys_get_temp_dir().'/wiki-'.md5($repoPath.time());
        $cloneUrl = "https://{$this->token}@github.com/{$repoPath}.wiki.git";

        $process = proc_open(
            ['git', 'clone', '--depth', '1', $cloneUrl, $clonePath],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        if (! is_resource($process)) {
            throw new \Exception('Failed to start git clone process');
        }

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new \Exception("Failed to clone wiki repository for '{$repoPath}': {$stderr}");
        }

        return $clonePath;
    }

    /**
     * Parse all markdown files in the cloned wiki directory
     *
     * @return array<int, array{slug: string, title: string}>
     */
    protected function parseWikiDirectory(string $clonePath): array
    {
        $pages = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($clonePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $relativePath = str_replace($clonePath.'/', '', $file->getPathname());

            // Skip hidden files and the .git directory
            if (str_starts_with($relativePath, '.')) {
                continue;
            }

            // Convert filename to slug (remove .md extension)
            $slug = preg_replace('/\.md$/', '', $relativePath);

            // Convert filename to title (replace hyphens with spaces)
            $title = str_replace('-', ' ', basename($slug));

            $pages[] = [
                'slug' => $slug,
                'title' => $title,
            ];
        }

        return $pages;
    }

    /**
     * Read a specific wiki page from the cloned directory
     *
     * @return array{slug: string, title: string, content: string}
     *
     * @throws \Exception
     */
    protected function readWikiPage(string $clonePath, string $slug): array
    {
        $filePath = "{$clonePath}/{$slug}.md";

        if (! file_exists($filePath)) {
            throw new \Exception("Wiki page '{$slug}' not found");
        }

        $content = file_get_contents($filePath);
        $title = str_replace('-', ' ', basename($slug));

        return [
            'slug' => $slug,
            'title' => $title,
            'content' => $content,
        ];
    }

    /**
     * Recursively remove a directory
     */
    protected function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
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
        // Note: Branch names with slashes (e.g., feature/branch) are ambiguous
        // in GitHub URLs and cannot be reliably parsed without an API call to
        // resolve the ref. This pattern assumes single-segment branch names.

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

        $response = Http::withHeaders($headers)->timeout(30)->get("{$this->baseUrl}/{$endpoint}");

        if ($response->status() === 403 && $response->header('X-RateLimit-Remaining') === '0') {
            $resetTime = (int) $response->header('X-RateLimit-Reset');
            $waitSeconds = max(0, $resetTime - time());

            throw new \Exception("GitHub API rate limit exceeded. Resets in {$waitSeconds} seconds.");
        }

        return $response;
    }
}
