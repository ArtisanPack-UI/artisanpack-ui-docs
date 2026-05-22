<?php

namespace App\Services;

use App\Contracts\WikiServiceInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubDocsService implements WikiServiceInterface
{
    /**
     * Directory names that are never imported as documentation.
     *
     * @var array<int, string>
     */
    protected const IGNORED_DIRS = ['plans', 'design', 'benchmarks', 'node_modules', 'vendor', '.git'];

    public function __construct(
        private string $token,
        private string $baseUrl = 'https://api.github.com'
    ) {}

    /**
     * Get all documentation pages with their content from a repository's docs/ directory
     *
     * Clones the package repository (not the wiki repo), reads markdown files from the
     * docs/ directory (or a directory specified via a /tree/{ref}/{path} URL), parses
     * the hierarchy, and returns all pages with content in a single operation. Raw file
     * content is returned with front matter intact; the import job is responsible for
     * stripping it.
     *
     * @param  string  $docsUrl  Repository or docs directory URL (e.g. https://github.com/owner/repo or .../tree/main/docs)
     * @return array<int, array{slug: string, title: string, content: string}>
     *
     * @throws \Exception
     */
    public function getWikiPagesWithContent(string $docsUrl): array
    {
        [$ref, $subdirectory] = $this->extractRefAndSubdirectory($docsUrl);

        $clonePath = $this->cloneRepo($this->extractRepoPath($docsUrl), $ref);

        try {
            $docsPath = realpath($clonePath.'/'.trim($subdirectory, '/'));

            if ($docsPath === false || ! is_dir($docsPath)) {
                throw new \Exception("No '{$subdirectory}' directory found in repository '{$this->extractRepoPath($docsUrl)}'");
            }

            return $this->collectPages($docsPath);
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
     * Collect documentation pages from a docs directory on disk
     *
     * Applies ignore rules, builds normalized hierarchical slugs, and deduplicates
     * section pages using the priority: index/README inside a directory > same-name
     * file inside the directory > same-name file at the docs root.
     *
     * @return array<int, array{slug: string, title: string, content: string}>
     *
     * @throws \Exception
     */
    public function collectPages(string $docsPath): array
    {
        $ignorePatterns = $this->readDocsIgnore($docsPath);

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($docsPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $files[] = str_replace($docsPath.'/', '', $file->getPathname());
        }

        // Sort for deterministic ordering before deduplication
        sort($files);

        $byslug = [];

        foreach ($files as $relativePath) {
            if ($this->shouldIgnore($relativePath, $ignorePatterns)) {
                continue;
            }

            $content = file_get_contents($docsPath.'/'.$relativePath);

            if ($content === false) {
                throw new \Exception("Failed to read documentation file '{$relativePath}'");
            }

            $frontMatter = $this->parseFrontMatter($content);

            if ($this->isTruthy($frontMatter['draft'] ?? null) || $this->isTruthy($frontMatter['hidden'] ?? null)) {
                continue;
            }

            [$slug, $rank] = $this->buildSlug($relativePath);

            // Keep the highest-priority file for a given slug; ties keep the first seen.
            if (! isset($byslug[$slug]) || $rank > $byslug[$slug]['rank']) {
                $byslug[$slug] = [
                    'slug' => $slug,
                    'title' => $this->humanizeSlugSegment($slug),
                    'content' => $content,
                    'rank' => $rank,
                ];
            }
        }

        return array_values(array_map(fn (array $page) => [
            'slug' => $page['slug'],
            'title' => $page['title'],
            'content' => $page['content'],
        ], $byslug));
    }

    /**
     * Build a normalized hierarchical slug and priority rank for a file path
     *
     * Rank: 3 = index/README inside a directory, 2 = same-name file inside its directory,
     * 1 = same-name file at the docs root, 0 = a regular nested page.
     *
     * @return array{0: string, 1: int}
     */
    protected function buildSlug(string $relativePath): array
    {
        $noExtension = preg_replace('/\.md$/i', '', $relativePath);
        $segments = explode('/', $noExtension);
        $base = end($segments);
        $directorySegments = array_slice($segments, 0, -1);

        if (in_array(strtolower($base), ['index', 'readme'], true)) {
            $slugSegments = $directorySegments;
            $rank = 3;
        } elseif (count($segments) >= 2 && $this->toKebab($segments[count($segments) - 2]) === $this->toKebab($base)) {
            $slugSegments = $directorySegments;
            $rank = 2;
        } else {
            $slugSegments = $segments;
            $rank = count($segments) === 1 ? 1 : 0;
        }

        $slug = implode('/', array_map(fn (string $segment) => $this->toKebab($segment), $slugSegments));

        // A root index/README has no directory segments — treat it as the home page.
        if ($slug === '') {
            $slug = 'home';
        }

        return [$slug, $rank];
    }

    /**
     * Determine whether a documentation file should be skipped
     *
     * @param  array<int, string>  $ignorePatterns
     */
    protected function shouldIgnore(string $relativePath, array $ignorePatterns): bool
    {
        $segments = explode('/', $relativePath);
        $base = end($segments);

        // Skip wiki chrome and hidden files (e.g. _Sidebar.md, _Footer.md, .foo.md)
        if (str_starts_with($base, '_') || str_starts_with($base, '.')) {
            return true;
        }

        foreach (array_slice($segments, 0, -1) as $directory) {
            if (in_array(strtolower($directory), self::IGNORED_DIRS, true)) {
                return true;
            }
        }

        return $this->matchesIgnorePattern($relativePath, $base, $ignorePatterns);
    }

    /**
     * Read .docsignore patterns from the docs directory if present
     *
     * @return array<int, string>
     */
    protected function readDocsIgnore(string $docsPath): array
    {
        $ignoreFile = $docsPath.'/.docsignore';

        if (! is_file($ignoreFile)) {
            return [];
        }

        $lines = file($ignoreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $lines), function (string $line) {
            return $line !== '' && ! str_starts_with($line, '#');
        }));
    }

    /**
     * Match a relative path against gitignore-style .docsignore patterns
     *
     * @param  array<int, string>  $patterns
     */
    protected function matchesIgnorePattern(string $relativePath, string $base, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '/')) {
                $directory = rtrim($pattern, '/');

                if (str_starts_with($relativePath, $directory.'/') || in_array($directory, explode('/', dirname($relativePath)), true)) {
                    return true;
                }

                continue;
            }

            if (fnmatch($pattern, $relativePath) || fnmatch($pattern, $base)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a leading YAML front matter block into a key/value map
     *
     * @return array<string, string>
     */
    protected function parseFrontMatter(string $content): array
    {
        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            return [];
        }

        $data = [];

        foreach (explode("\n", $matches[1]) as $line) {
            if (preg_match('/^\s*([A-Za-z0-9_-]+)\s*:\s*(.*)$/', $line, $pair)) {
                $data[strtolower(trim($pair[1]))] = trim($pair[2], " \t\"'");
            }
        }

        return $data;
    }

    /**
     * Determine whether a front matter value represents a truthy flag
     */
    protected function isTruthy(?string $value): bool
    {
        return $value !== null && in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Normalize a path segment to a kebab-case slug segment
     */
    protected function toKebab(string $segment): string
    {
        $segment = preg_replace('/[\s_]+/', '-', trim($segment));
        $segment = preg_replace('/-+/', '-', $segment);

        return strtolower(trim($segment, '-'));
    }

    /**
     * Build a human-readable title from the last segment of a slug
     */
    protected function humanizeSlugSegment(string $slug): string
    {
        $segment = str_contains($slug, '/') ? substr($slug, strrpos($slug, '/') + 1) : $slug;

        return ucwords(str_replace('-', ' ', $segment));
    }

    /**
     * Extract owner/repo from a GitHub URL
     *
     * @throws \Exception
     */
    protected function extractRepoPath(string $url): string
    {
        if (preg_match('/github\.com\/([^\/]+\/[^\/]+)/', $url, $matches)) {
            return preg_replace('/\.git$/', '', $matches[1]);
        }

        throw new \Exception("Invalid GitHub URL format: {$url}");
    }

    /**
     * Extract the ref (branch) and docs subdirectory from a docs URL
     *
     * Supports plain repository URLs (defaults to the default branch and the docs/
     * directory) and tree URLs such as https://github.com/owner/repo/tree/main/docs.
     *
     * @return array{0: ?string, 1: string} [ref, subdirectory]
     */
    protected function extractRefAndSubdirectory(string $url): array
    {
        if (preg_match('/github\.com\/[^\/]+\/[^\/]+\/tree\/([^\/]+)\/(.+?)\/?$/', $url, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return [null, 'docs'];
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
        if (preg_match('/github\.com\/([^\/]+\/[^\/]+)\/blob\/([^\/]+)\/(.+)/', $fileUrl, $matches)) {
            return [$matches[1], $matches[3], $matches[2]];
        }

        if (preg_match('/raw\.githubusercontent\.com\/([^\/]+\/[^\/]+)\/([^\/]+)\/(.+)/', $fileUrl, $matches)) {
            return [$matches[1], $matches[3], $matches[2]];
        }

        throw new \Exception("Invalid GitHub file URL format: {$fileUrl}");
    }

    /**
     * Clone the package Git repository to a temporary directory
     *
     * Uses GIT_ASKPASS to supply credentials securely instead of embedding the token
     * in the clone URL.
     *
     * @throws \Exception
     */
    protected function cloneRepo(string $repoPath, ?string $ref = null): string
    {
        $clonePath = sys_get_temp_dir().'/docs-'.bin2hex(random_bytes(8));
        $cloneUrl = "https://github.com/{$repoPath}.git";

        $escapedToken = str_replace("'", "'\\''", $this->token);
        $askpassScript = tempnam(sys_get_temp_dir(), 'git-askpass-');
        file_put_contents($askpassScript, "#!/bin/sh\necho '{$escapedToken}'\n");
        chmod($askpassScript, 0700);

        try {
            $env = [
                'GIT_ASKPASS' => $askpassScript,
                'GIT_TERMINAL_PROMPT' => '0',
            ];

            $command = ['git', 'clone', '--depth', '1'];

            if ($ref !== null) {
                $command[] = '--branch';
                $command[] = $ref;
            }

            $command[] = $cloneUrl;
            $command[] = $clonePath;

            $process = proc_open(
                $command,
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                null,
                $env
            );

            if (! is_resource($process)) {
                throw new \Exception('Failed to start git clone process');
            }

            $timeoutSeconds = 120;
            $startTime = time();
            $timedOut = false;

            stream_set_blocking($pipes[2], false);
            stream_set_blocking($pipes[1], false);

            $stderr = '';
            while (true) {
                $status = proc_get_status($process);
                if (! $status['running']) {
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

                $sanitizedStderr = preg_replace('/(?:gh[pousr]_|gha_|github_pat_)[A-Za-z0-9_]+/', '[REDACTED]', $stderr);

                throw new \Exception("Failed to clone repository for '{$repoPath}': {$sanitizedStderr}");
            }

            return $clonePath;
        } finally {
            @unlink($askpassScript);
        }
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
