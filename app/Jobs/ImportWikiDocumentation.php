<?php

namespace App\Jobs;

use App\Services\GitLabService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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

                // Create or update documentation
                Documentation::updateOrCreate(
                    [
                        'slug' => $wikiPage['slug'],
                        'package_id' => $this->package->id,
                    ],
                    [
                        'title' => $fullPage['title'] ?? $wikiPage['title'] ?? $wikiPage['slug'],
                        'content' => $fullPage['content'] ?? '',
                        'parent' => null, // GitLab wiki pages don't have a parent hierarchy by default
                    ]
                );

                Log::info('Imported wiki page: {slug}', ['slug' => $wikiPage['slug']]);
            }

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
}
