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
                $pageData = $this->readWikiPage($clonePath, $page['file']);

                return [
                    'slug' => $page['slug'],
                    'title' => $page['title'],
                    'content' => $pageData['content'],
                ];
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
     * Uses GIT_ASKPASS to supply credentials securely instead of embedding
     * the token in the clone URL.
     *
     * @throws \Exception
     */
    protected function cloneWikiRepo(string $wikiUrl): string
    {
        $repoPath = $this->extractRepoPath($wikiUrl);
        $clonePath = sys_get_temp_dir().'/wiki-'.bin2hex(random_bytes(8));
        $cloneUrl = "https://github.com/{$repoPath}.wiki.git";

        // Create a temporary GIT_ASKPASS script that echoes the token
        $askpassScript = tempnam(sys_get_temp_dir(), 'git-askpass-');
        file_put_contents($askpassScript, "#!/bin/sh\necho '{$this->token}'\n");
        chmod($askpassScript, 0700);

        try {
            $env = [
                'GIT_ASKPASS' => $askpassScript,
                'GIT_TERMINAL_PROMPT' => '0',
            ];

            $process = proc_open(
                ['git', 'clone', '--depth', '1', $cloneUrl, $clonePath],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                null,
                $env
            );

            if (! is_resource($process)) {
                throw new \Exception('Failed to start git clone process');
            }

            // Enforce a 120-second timeout on the clone
            $timeoutSeconds = 120;
            $startTime = time();
            $timedOut = false;

            // Set stderr to non-blocking so we can poll
            stream_set_blocking($pipes[2], false);
            stream_set_blocking($pipes[1], false);

            $stderr = '';
            while (true) {
                $status = proc_get_status($process);
                if (! $status['running']) {
                    // Process finished — read remaining stderr
                    $stderr .= stream_get_contents($pipes[2]);

                    break;
                }

                if ((time() - $startTime) >= $timeoutSeconds) {
                    $timedOut = true;
                    proc_terminate($process, 9);

                    break;
                }

                $stderr .= stream_get_contents($pipes[2]);
                usleep(100_000); // 100ms
            }

            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($timedOut) {
                $this->removeDirectory($clonePath);

                throw new \Exception("Git clone timed out after {$timeoutSeconds} seconds for '{$repoPath}'");
            }

            if ($exitCode !== 0) {
                $this->removeDirectory($clonePath);

                // Redact any token-like strings from stderr before throwing
                $sanitizedStderr = preg_replace('/gh[pousr]_[A-Za-z0-9]+/', '[REDACTED]', $stderr);

                throw new \Exception("Failed to clone wiki repository for '{$repoPath}': {$sanitizedStderr}");
            }

            return $clonePath;
        } finally {
            @unlink($askpassScript);
        }
    }

    /**
     * Parse all markdown files in the cloned wiki directory
     *
     * @return array<int, array{slug: string, title: string, file: string}>
     */
    protected function parseWikiDirectory(string $clonePath): array
    {
        $pages = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($clonePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $relativePath = str_replace($clonePath.'/', '', $file->getPathname());

            // Skip hidden files and the .git directory
            if (str_starts_with($relativePath, '.')) {
                continue;
            }

            // Convert filename to slug (remove .md/.MD extension, case-insensitive)
            $slug = preg_replace('/\.md$/i', '', $relativePath);

            // Convert filename to title (replace hyphens with spaces)
            $title = str_replace('-', ' ', basename($slug));

            $pages[] = [
                'slug' => $slug,
                'title' => $title,
                'file' => $relativePath,
            ];
        }

        return $pages;
    }

    /**
     * Read a specific wiki page from the cloned directory
     *
     * @param  string  $clonePath  The clone directory path
     * @param  string  $relativeFile  The relative file path within the clone (e.g., "guides/usage.md")
     * @return array{content: string}
     *
     * @throws \Exception
     */
    protected function readWikiPage(string $clonePath, string $relativeFile): array
    {
        $filePath = "{$clonePath}/{$relativeFile}";

        $cloneReal = realpath($clonePath);
        $fileReal = realpath($filePath);

        if ($fileReal === false || $cloneReal === false || ! str_starts_with($fileReal, $cloneReal.DIRECTORY_SEPARATOR)) {
            throw new \Exception("Wiki page '{$relativeFile}' not found or path is outside repository");
        }

        $content = file_get_contents($fileReal);

        if ($content === false) {
            throw new \Exception("Failed to read wiki page '{$relativeFile}' at '{$fileReal}'");
        }

        return [
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
        // https://raw.githubusercontent.com/owner/repo/main/CHANGELOG.md
        // Note: Branch names with slashes (e.g., feature/branch) are ambiguous
        // in GitHub URLs and cannot be reliably parsed without an API call to
        // resolve the ref. This pattern assumes single-segment branch names.

        // Match github.com blob URLs
        $blobPattern = '/github\.com\/([^\/]+\/[^\/]+)\/blob\/([^\/]+)\/(.+)/';
        if (preg_match($blobPattern, $fileUrl, $matches)) {
            return [
                $matches[1], // repo path (owner/repo)
                $matches[3], // file path
                $matches[2], // ref (branch)
            ];
        }

        // Match raw.githubusercontent.com URLs
        $rawPattern = '/raw\.githubusercontent\.com\/([^\/]+\/[^\/]+)\/([^\/]+)\/(.+)/';
        if (preg_match($rawPattern, $fileUrl, $matches)) {
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
