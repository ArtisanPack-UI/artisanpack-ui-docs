<?php

namespace App\Jobs;

use App\Services\GitLabService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Packages\Changelog;
use Modules\Packages\Package;

class ImportChangelog implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

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

            // Fetch the changelog content from GitLab
            $content = $gitlabService->getFileContent($this->package->changelog_url);

            // Remove the first H1 header if it exists
            $content = $this->removeFirstH1Header($content);

            // Create the changelog title
            $title = "{$this->package->name} Changelog";

            // Create or update the changelog
            Changelog::updateOrCreate(
                [
                    'package_id' => $this->package->id,
                ],
                [
                    'title' => $title,
                    'content' => $content,
                ]
            );

            Log::info('Successfully imported changelog for package {package}', [
                'package' => $this->package->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to import changelog for package {package}: {error}', [
                'package' => $this->package->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Remove the first H1 header from content
     */
    protected function removeFirstH1Header(string $content): string
    {
        // Remove first H1 header (single # followed by space) and any trailing newlines
        // This handles optional leading whitespace and various line endings
        return preg_replace('/^\s*#\s+[^\n\r]+[\n\r]*/m', '', $content, 1);
    }
}
