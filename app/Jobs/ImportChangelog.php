<?php

namespace App\Jobs;

use App\Services\WikiServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Core\Setting;
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
        public Package $package
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $factory = app()->make(WikiServiceFactory::class);
            $source = $factory->detectSource($this->package->changelog_url);
            $token = $this->resolveToken($source);
            $wikiService = $factory->make($this->package->changelog_url, $token);

            // Fetch the changelog content
            $content = $wikiService->getFileContent($this->package->changelog_url);

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
     * Resolve the API token from encrypted settings based on the detected source
     *
     * @throws \Exception
     */
    protected function resolveToken(string $source): string
    {
        $settingKey = "{$source}_token";
        $brandName = match ($source) {
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            default => ucfirst($source),
        };

        $encryptedToken = Setting::where('key', $settingKey)->first()?->value;

        try {
            $token = $encryptedToken ? decrypt($encryptedToken) : null;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $token = null;
        }

        if (empty($token)) {
            throw new \Exception("{$brandName} token not configured or could not be decrypted");
        }

        return $token;
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
