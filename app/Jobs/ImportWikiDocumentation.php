<?php

namespace App\Jobs;

use App\Services\GitLabService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

class ImportWikiDocumentation implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Package $package,
        public string $gitlabToken
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $gitlabService = new GitLabService($this->gitlabToken);

            // Get all wiki pages
            $wikiPages = $gitlabService->getWikiPages($this->package->wiki_url);

            Log::info('Found {count} wiki pages for package {package}', [
                'count' => count($wikiPages),
                'package' => $this->package->name,
            ]);

            foreach ($wikiPages as $wikiPage) {
                // Get full content for each page
                $fullPage = $gitlabService->getWikiPage($this->package->wiki_url, $wikiPage['slug']);

                $content = $fullPage['content'] ?? '';

                // Extract title from YAML front matter and remove it
                $yamlTitle = $this->extractTitleFromYaml($content);
                $content = $this->removeYamlFrontMatter($content);

                // Extract title from H1 header and remove it
                $h1Title = $this->extractTitleFromH1($content);
                $content = $this->removeFirstH1Header($content);

                // Clean and update links
                $cleanedContent = $this->updateInternalLinks($content);

                // Title priority: YAML > H1 > Title case GitLab title > slug
                $gitlabTitle = $fullPage['title'] ?? $wikiPage['title'] ?? null;
                $title = $yamlTitle ?? $h1Title ?? ($gitlabTitle ? $this->toTitleCase($gitlabTitle) : $wikiPage['slug']);

                // Create or update documentation
                Documentation::updateOrCreate(
                    [
                        'slug' => $wikiPage['slug'],
                        'package_id' => $this->package->id,
                    ],
                    [
                        'title' => $title,
                        'content' => $cleanedContent,
                        'parent' => null, // Will be set in the next step
                    ]
                );

                Log::info('Imported wiki page: {slug}', ['slug' => $wikiPage['slug']]);
            }

            // Set up parent relationships for subpages
            $this->setParentRelationships();

            Log::info('Successfully imported {count} wiki pages for package {package}', [
                'count' => count($wikiPages),
                'package' => $this->package->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to import wiki documentation for package {package}: {error}', [
                'package' => $this->package->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract title from YAML front matter (format: ---\ntitle: Title\n---)
     */
    protected function extractTitleFromYaml(string $content): ?string
    {
        // Match YAML front matter with title field
        if (preg_match('/^---\s*\n.*?title:\s*(.+?)\s*\n.*?---\s*\n/s', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Remove the YAML front matter from content
     */
    protected function removeYamlFrontMatter(string $content): string
    {
        // Remove YAML front matter block (everything between --- and ---)
        return preg_replace('/^---\s*\n.*?---\s*\n/s', '', $content);
    }

    /**
     * Extract title from first H1 markdown header (format: # Title)
     */
    protected function extractTitleFromH1(string $content): ?string
    {
        // Match first H1 header (# Title)
        if (preg_match('/^#\s+(.+?)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Remove the first H1 header from content
     */
    protected function removeFirstH1Header(string $content): string
    {
        // Remove first H1 header and any trailing whitespace
        return preg_replace('/^#\s+.+?$\n*/m', '', $content, 1);
    }

    /**
     * Convert string to title case
     */
    protected function toTitleCase(string $text): string
    {
        // Words that should remain lowercase in titles
        $minorWords = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'of', 'on', 'or', 'the', 'to', 'with'];

        $words = explode(' ', mb_strtolower($text));
        $titleCased = [];

        foreach ($words as $index => $word) {
            // Always capitalize first and last word, or if not a minor word
            if ($index === 0 || $index === count($words) - 1 || ! in_array($word, $minorWords)) {
                $titleCased[] = mb_convert_case($word, MB_CASE_TITLE);
            } else {
                $titleCased[] = $word;
            }
        }

        return implode(' ', $titleCased);
    }

    /**
     * Update internal documentation links to new format
     */
    protected function updateInternalLinks(string $content): string
    {
        // Get the site URL
        $siteUrl = rtrim(config('app.url'), '/');

        // Pattern to match wiki links (both relative and absolute)
        // This will match links like: [text](page-slug) or [text](parent/page-slug)
        $pattern = '/\[([^\]]+)\]\((?!https?:\/\/)([^)]+)\)/';

        return preg_replace_callback($pattern, function ($matches) use ($siteUrl) {
            $linkText = $matches[1];
            $originalPath = $matches[2];

            // Remove any leading slashes or wiki-specific prefixes
            $path = ltrim($originalPath, '/');
            $path = preg_replace('/^(wikis?\/|\.\.?\/)/', '', $path);

            // Build the new URL
            $newUrl = "{$siteUrl}/documentation/{$this->package->slug}/{$path}";

            return "[{$linkText}]({$newUrl})";
        }, $content);
    }

    /**
     * Set parent relationships for subpages (keeps original slugs)
     */
    protected function setParentRelationships(): void
    {
        $docs = Documentation::where('package_id', $this->package->id)
            ->where('slug', 'like', '%/%')
            ->get();

        foreach ($docs as $doc) {
            // Extract parent slug from the slug (e.g., "guides/usage" -> "guides")
            if (str_contains($doc->slug, '/')) {
                $parts = explode('/', $doc->slug);
                $parentSlug = $parts[0];

                // Find the parent documentation
                $parent = Documentation::where('package_id', $this->package->id)
                    ->where('slug', $parentSlug)
                    ->first();

                if ($parent) {
                    // Set parent without changing the slug
                    DB::table('documentation')
                        ->where('id', $doc->id)
                        ->update([
                            'parent' => $parent->id,
                            'updated_at' => now(),
                        ]);

                    Log::info('Set parent for {slug}: parent={parent}', [
                        'slug' => $doc->slug,
                        'parent' => $parent->slug,
                    ]);
                }
            }
        }
    }
}
