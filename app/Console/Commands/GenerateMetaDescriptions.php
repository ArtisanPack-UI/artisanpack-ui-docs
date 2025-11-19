<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Packages\Documentation;
use Modules\Pages\Page;

class GenerateMetaDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:generate-meta-descriptions
                            {--pages : Only generate for pages}
                            {--documentation : Only generate for documentation}
                            {--overwrite : Overwrite existing meta descriptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate meta descriptions from content for pages and documentation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $onlyPages = $this->option('pages');
        $onlyDocumentation = $this->option('documentation');
        $overwrite = $this->option('overwrite');

        $processPages = ! $onlyDocumentation || $onlyPages;
        $processDocumentation = ! $onlyPages || $onlyDocumentation;

        $totalUpdated = 0;

        if ($processPages) {
            $pagesUpdated = $this->generateForPages($overwrite);
            $totalUpdated += $pagesUpdated;
            $this->info("Updated {$pagesUpdated} page(s)");
        }

        if ($processDocumentation) {
            $docsUpdated = $this->generateForDocumentation($overwrite);
            $totalUpdated += $docsUpdated;
            $this->info("Updated {$docsUpdated} documentation page(s)");
        }

        $this->newLine();
        $this->info("Total updated: {$totalUpdated}");

        return Command::SUCCESS;
    }

    /**
     * Generate meta descriptions for pages
     */
    protected function generateForPages(bool $overwrite): int
    {
        $query = Page::query();

        if (! $overwrite) {
            $query->where(function ($q) {
                $q->whereNull('meta_description')
                    ->orWhere('meta_description', '');
            });
        }

        $pages = $query->get();
        $updated = 0;

        foreach ($pages as $page) {
            $metaDescription = $this->generateFromHtml($page->content);

            if ($metaDescription) {
                $page->update(['meta_description' => $metaDescription]);
                $updated++;
                $this->line("  - {$page->title}");
            }
        }

        return $updated;
    }

    /**
     * Generate meta descriptions for documentation
     */
    protected function generateForDocumentation(bool $overwrite): int
    {
        $query = Documentation::query();

        if (! $overwrite) {
            $query->where(function ($q) {
                $q->whereNull('meta_description')
                    ->orWhere('meta_description', '');
            });
        }

        $docs = $query->get();
        $updated = 0;

        foreach ($docs as $doc) {
            $metaDescription = $this->generateFromMarkdown($doc->content);

            if ($metaDescription) {
                $doc->update(['meta_description' => $metaDescription]);
                $updated++;
                $this->line("  - {$doc->title}");
            }
        }

        return $updated;
    }

    /**
     * Generate meta description from HTML content
     */
    protected function generateFromHtml(string $content): string
    {
        // Strip HTML tags
        $text = strip_tags($content);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $this->truncateToLimit($text);
    }

    /**
     * Generate meta description from Markdown content
     */
    protected function generateFromMarkdown(string $content): string
    {
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

        return $this->truncateToLimit($text);
    }

    /**
     * Truncate text to 155 characters on word boundary
     */
    protected function truncateToLimit(string $text): string
    {
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
}
