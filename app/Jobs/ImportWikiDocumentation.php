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
            // A configured docs_url takes priority over wiki_url; wiki_url is the fallback.
            $useDocsSource = ! empty($this->package->docs_url);
            $sourceUrl = $useDocsSource ? $this->package->docs_url : $this->package->wiki_url;

            $factory = app()->make(WikiServiceFactory::class);
            $source = $factory->detectSource($sourceUrl);
            $token = $this->resolveToken($source);
            $wikiService = $useDocsSource
                ? $factory->makeDocsService($sourceUrl, $token)
                : $factory->make($sourceUrl, $token);

            // Get all pages with content
            $wikiPages = $wikiService->getWikiPagesWithContent($sourceUrl);

            Log::info('Found {count} wiki pages for package {package}', [
                'count' => count($wikiPages),
                'package' => $this->package->name,
            ]);

            // When importing from a docs/ directory, build a slug lookup so that flat
            // wiki-style links (e.g. "Advanced-Webhooks") can be translated to the
            // hierarchical docs slugs (e.g. "advanced/webhooks").
            $slugLookup = $useDocsSource ? $this->buildSlugLookup($wikiPages) : [];
            $parentOverrides = [];

            foreach ($wikiPages as $wikiPage) {
                $content = $wikiPage['content'] ?? '';

                // Parse the full front matter, then extract title and strip the block
                $frontMatter = $this->parseFrontMatter($content);
                $yamlTitle = $this->extractTitleFromYaml($content);
                $content = $this->removeYamlFrontMatter($content);

                // Extract title from H1 header and remove it
                $h1Title = $this->extractTitleFromH1($content);
                $content = $this->removeFirstH1Header($content);

                // Clean and update links
                $cleanedContent = $useDocsSource
                    ? $this->updateDocsLinks($content, $wikiPage['slug'], $slugLookup)
                    : $this->updateInternalLinks($content);

                // Title priority: YAML > H1 > Title case source title > slug
                $githubTitle = $wikiPage['title'] ?? null;
                $title = $yamlTitle ?? $h1Title ?? ($githubTitle ? $this->toTitleCase($githubTitle) : $wikiPage['slug']);

                // Meta description: front matter override, otherwise generated from content
                $metaDescription = ! empty($frontMatter['meta_description'])
                    ? $frontMatter['meta_description']
                    : $this->generateMetaDescription($cleanedContent);

                $attributes = [
                    'title' => $title,
                    'content' => $cleanedContent,
                    'meta_description' => $metaDescription,
                    'parent' => null, // Will be set in the next step
                ];

                // Only set menu_order from front matter so admin drag-and-drop ordering
                // is preserved across re-imports when no explicit order is provided.
                if (isset($frontMatter['menu_order']) && $frontMatter['menu_order'] !== '') {
                    $attributes['menu_order'] = (int) $frontMatter['menu_order'];
                }

                if (! empty($frontMatter['parent'])) {
                    $parentOverrides[$wikiPage['slug']] = $frontMatter['parent'];
                }

                // Create or update documentation
                Documentation::updateOrCreate(
                    [
                        'slug' => $wikiPage['slug'],
                        'package_id' => $this->package->id,
                    ],
                    $attributes
                );

                Log::info('Imported wiki page: {slug}', ['slug' => $wikiPage['slug']]);
            }

            // Remove documentation pages that no longer exist in the source
            $importedSlugs = array_column($wikiPages, 'slug');
            $deleted = Documentation::where('package_id', $this->package->id)
                ->whereNotIn('slug', $importedSlugs)
                ->delete();

            if ($deleted > 0) {
                Log::info('Removed {count} stale documentation pages for package {package}', [
                    'count' => $deleted,
                    'package' => $this->package->name,
                ]);
            }

            // Set up parent relationships for subpages
            $this->setParentRelationships($parentOverrides);

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

        // First, rewrite GitHub wiki [[Page Name]] and [[Page Name|Display Text]] wikilinks
        $content = preg_replace_callback(
            '/\[\[([^\]|]+?)(?:\|([^\]]+?))?\]\]/',
            function ($matches) use ($siteUrl) {
                $pageName = trim($matches[1]);
                $displayText = isset($matches[2]) ? trim($matches[2]) : $pageName;
                $slug = str_replace(' ', '-', $pageName);

                $newUrl = "{$siteUrl}/documentation/{$this->package->slug}/{$slug}";

                return "[{$displayText}]({$newUrl})";
            },
            $content
        );

        // Then, rewrite absolute GitHub wiki URLs
        $content = preg_replace_callback(
            '/\[([^\]]+)\]\(('.$escapedWikiBase.'\/([^)#?\s]+))([^)]*)\)/',
            function ($matches) use ($siteUrl) {
                $linkText = $matches[1];
                $pageName = preg_replace('/\.md$/i', '', $matches[3]);
                $suffix = $matches[4]; // anchors, query strings

                $newUrl = "{$siteUrl}/documentation/{$this->package->slug}/{$pageName}";

                return "[{$linkText}]({$newUrl}{$suffix})";
            },
            $content
        );

        // Finally, rewrite relative wiki links
        $pattern = '/\[([^\]]+)\]\((?!https?:\/\/)([^)]+)\)/';

        return preg_replace_callback($pattern, function ($matches) use ($siteUrl) {
            $linkText = $matches[1];
            $originalPath = $matches[2];

            // Remove any leading slashes or wiki-specific prefixes
            $path = ltrim($originalPath, '/');
            $path = preg_replace('/^(wikis?\/|\.\.?\/)/', '', $path);

            // Split off anchor/query suffix before stripping .md
            $suffix = '';
            if (preg_match('/^([^#?]+)([#?].*)$/', $path, $pathParts)) {
                $path = $pathParts[1];
                $suffix = $pathParts[2];
            }

            $path = preg_replace('/\.md$/i', '', $path);

            $newUrl = "{$siteUrl}/documentation/{$this->package->slug}/{$path}";

            return "[{$linkText}]({$newUrl}{$suffix})";
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
     *
     * A front matter `parent` slug, when provided, overrides the parent inferred from
     * the slug's directory structure.
     *
     * @param  array<string, string>  $parentOverrides  Map of page slug to overriding parent slug
     */
    protected function setParentRelationships(array $parentOverrides = []): void
    {
        $docs = Documentation::where('package_id', $this->package->id)->get();

        foreach ($docs as $doc) {
            $parentSlug = $parentOverrides[$doc->slug] ?? $this->inferParentSlug($doc->slug);

            if ($parentSlug === null || $parentSlug === $doc->slug) {
                continue;
            }

            // Find the parent documentation
            $parent = Documentation::where('package_id', $this->package->id)
                ->where('slug', $parentSlug)
                ->first();

            if ($parent && $parent->id !== $doc->id) {
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

    /**
     * Infer a page's parent slug from its own slug's directory structure
     *
     * For example, "advanced/usage/webhooks" -> "advanced/usage".
     */
    protected function inferParentSlug(string $slug): ?string
    {
        if (! str_contains($slug, '/')) {
            return null;
        }

        return substr($slug, 0, strrpos($slug, '/'));
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
     * Build a lookup map from a normalized wiki-style page name to a docs slug
     *
     * The flat wiki namespace joins path segments with hyphens and loses the
     * distinction between "/" and "-", so "advanced/webhooks" and the link target
     * "Advanced-Webhooks" both normalize to "advanced-webhooks". The first slug seen
     * for a given normalized key wins.
     *
     * @param  array<int, array{slug: string, title: string, content: string}>  $pages
     * @return array<string, string>
     */
    protected function buildSlugLookup(array $pages): array
    {
        $lookup = [];

        foreach ($pages as $page) {
            $key = $this->normalizeLinkTarget($page['slug']);

            if (! isset($lookup[$key])) {
                $lookup[$key] = $page['slug'];
            }
        }

        return $lookup;
    }

    /**
     * Normalize a link target or slug for wiki-name matching
     */
    protected function normalizeLinkTarget(string $value): string
    {
        $value = preg_replace('/[\s_\/]+/', '-', trim($value));
        $value = preg_replace('/-+/', '-', $value);

        return strtolower(trim($value, '-'));
    }

    /**
     * Resolve a documentation link target to a known docs slug
     *
     * Tries a direct match first, then resolves relative to the current page's
     * directory. Returns null when no known slug matches.
     *
     * @param  array<string, string>  $slugLookup
     */
    protected function resolveDocsTarget(string $target, string $currentSlug, array $slugLookup): ?string
    {
        $clean = preg_replace('/\.md$/i', '', ltrim($target, '/'));
        $clean = preg_replace('#^\.\.?/#', '', $clean);

        $directory = str_contains($currentSlug, '/')
            ? substr($currentSlug, 0, strrpos($currentSlug, '/'))
            : $currentSlug;

        $candidates = [
            $clean,
            $directory.'/'.$clean,
        ];

        foreach ($candidates as $candidate) {
            $key = $this->normalizeLinkTarget($candidate);

            if (isset($slugLookup[$key])) {
                return $slugLookup[$key];
            }
        }

        return null;
    }

    /**
     * Update internal documentation links when importing from a docs/ directory
     *
     * Handles GitHub [[wikilinks]], absolute repository URLs, and relative/flat
     * wiki-name links, translating each to the docs site URL structure.
     *
     * @param  array<string, string>  $slugLookup
     */
    protected function updateDocsLinks(string $content, string $currentSlug, array $slugLookup): string
    {
        $siteUrl = rtrim(config('app.url'), '/');

        // Rewrite [[Page Name]] and [[Page Name|Display Text]] wikilinks
        $content = preg_replace_callback(
            '/\[\[([^\]|]+?)(?:\|([^\]]+?))?\]\]/',
            function ($matches) use ($siteUrl, $currentSlug, $slugLookup) {
                $pageName = trim($matches[1]);
                $displayText = isset($matches[2]) ? trim($matches[2]) : $pageName;
                $slug = $this->resolveDocsTarget($pageName, $currentSlug, $slugLookup)
                    ?? $this->normalizeLinkTarget($pageName);

                return "[{$displayText}]({$siteUrl}/documentation/{$this->package->slug}/{$slug})";
            },
            $content
        );

        // Rewrite markdown links that are not external URLs
        return preg_replace_callback(
            '/\[([^\]]+)\]\((?!https?:\/\/|mailto:|#)([^)]+)\)/',
            function ($matches) use ($siteUrl, $currentSlug, $slugLookup) {
                $linkText = $matches[1];
                $originalPath = $matches[2];

                // Split off anchor/query suffix
                $suffix = '';
                $path = $originalPath;
                if (preg_match('/^([^#?]*)([#?].*)$/', $path, $pathParts)) {
                    $path = $pathParts[1];
                    $suffix = $pathParts[2];
                }

                $resolved = $this->resolveDocsTarget($path, $currentSlug, $slugLookup);

                if ($resolved === null) {
                    // Fall back to treating the target as a relative path
                    $resolved = preg_replace('/\.md$/i', '', ltrim($path, '/'));
                    $resolved = preg_replace('#^\.\.?/#', '', $resolved);
                }

                return "[{$linkText}]({$siteUrl}/documentation/{$this->package->slug}/{$resolved}{$suffix})";
            },
            $content
        );
    }
}
