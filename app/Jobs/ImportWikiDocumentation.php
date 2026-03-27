<?php

namespace App\Jobs;

use App\Concerns\ResolvesServiceTokens;
use App\Services\WikiServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

class ImportWikiDocumentation implements ShouldQueue
{
    use Queueable;
    use ResolvesServiceTokens;

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
        public Package $package
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $factory = app()->make(WikiServiceFactory::class);
            $source = $factory->detectSource($this->package->wiki_url);
            $token = $this->resolveToken($source);
            $wikiService = $factory->make($this->package->wiki_url, $token);

            // Get all wiki pages with content
            $wikiPages = $wikiService->getWikiPagesWithContent($this->package->wiki_url);

            Log::info('Found {count} wiki pages for package {package}', [
                'count' => count($wikiPages),
                'package' => $this->package->name,
            ]);

            foreach ($wikiPages as $wikiPage) {
                $content = $wikiPage['content'] ?? '';

                // Extract title from YAML front matter and remove it
                $yamlTitle = $this->extractTitleFromYaml($content);
                $content = $this->removeYamlFrontMatter($content);

                // Extract title from H1 header and remove it
                $h1Title = $this->extractTitleFromH1($content);
                $content = $this->removeFirstH1Header($content);

                // Clean and update links
                $cleanedContent = $this->updateInternalLinks($content);

                // Title priority: YAML > H1 > Title case GitHub title > slug
                $githubTitle = $wikiPage['title'] ?? null;
                $title = $yamlTitle ?? $h1Title ?? ($githubTitle ? $this->toTitleCase($githubTitle) : $wikiPage['slug']);

                // Generate meta description from content
                $metaDescription = $this->generateMetaDescription($cleanedContent);

                // Create or update documentation
                Documentation::updateOrCreate(
                    [
                        'slug' => $wikiPage['slug'],
                        'package_id' => $this->package->id,
                    ],
                    [
                        'title' => $title,
                        'content' => $cleanedContent,
                        'meta_description' => $metaDescription,
                        'parent' => null, // Will be set in the next step
                    ]
                );

                Log::info('Imported wiki page: {slug}', ['slug' => $wikiPage['slug']]);
            }

            // Set up parent relationships for subpages
            $this->setParentRelationships();

            // Update the docs_imported_at timestamp
            $this->package->update(['docs_imported_at' => now()]);

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
        $siteUrl = rtrim(config('app.url'), '/');

        // Extract the wiki base URL from the package wiki_url for absolute link matching
        // e.g., https://github.com/owner/repo/wiki -> matches https://github.com/owner/repo/wiki/Page-Name
        $wikiBase = rtrim($this->package->wiki_url, '/');
        $escapedWikiBase = preg_quote($wikiBase, '/');

        // First, rewrite absolute GitHub wiki URLs
        $content = preg_replace_callback(
            '/\[([^\]]+)\]\(('.$escapedWikiBase.'\/([^)#?\s]+))([^)]*)\)/',
            function ($matches) use ($siteUrl) {
                $linkText = $matches[1];
                $pageName = $matches[3];
                $suffix = $matches[4]; // anchors, query strings

                $newUrl = "{$siteUrl}/documentation/{$this->package->slug}/{$pageName}";

                return "[{$linkText}]({$newUrl}{$suffix})";
            },
            $content
        );

        // Then, rewrite relative wiki links
        $pattern = '/\[([^\]]+)\]\((?!https?:\/\/)([^)]+)\)/';

        return preg_replace_callback($pattern, function ($matches) use ($siteUrl) {
            $linkText = $matches[1];
            $originalPath = $matches[2];

            // Remove any leading slashes or wiki-specific prefixes
            $path = ltrim($originalPath, '/');
            $path = preg_replace('/^(wikis?\/|\.\.?\/)/', '', $path);

            $newUrl = "{$siteUrl}/documentation/{$this->package->slug}/{$path}";

            return "[{$linkText}]({$newUrl})";
        }, $content);
    }

    /**
     * Generate a meta description from content by stripping tags and truncating
     */
    protected function generateMetaDescription(string $content): string
    {
        // Convert markdown to plain text
        $text = $content;

        // Remove markdown headers
        $text = preg_replace('/^#+\s+.+$/m', '', $text);

        // Remove markdown links but keep text
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);

        // Remove markdown bold/italic
        $text = preg_replace('/[*_]{1,3}([^*_]+)[*_]{1,3}/', '$1', $text);

        // Remove code blocks
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`[^`]+`/', '', $text);

        // Remove HTML tags
        $text = strip_tags($text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Truncate to 155 characters on word boundary
        if (strlen($text) > 155) {
            $text = substr($text, 0, 155);
            $lastSpace = strrpos($text, ' ');
            if ($lastSpace !== false) {
                $text = substr($text, 0, $lastSpace);
            }
            $text = rtrim($text, '.,;:!?').'...';
        }

        return $text;
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
