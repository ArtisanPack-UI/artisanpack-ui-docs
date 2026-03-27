<?php

namespace Modules\Admin\Livewire;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Setting;
use Modules\Pages\Page;

#[Layout('admin::layouts.admin')]
class SettingsPage extends Component
{
    use Toast;

    public ?string $gitLabToken = '';

    public ?string $gitHubToken = '';

    public ?string $homePage = '';

    public ?string $googleAnalyticsId = '';

    public function mount(): void
    {
        $settings = Setting::get();

        // Decrypt the GitLab token when retrieving it
        $encryptedToken = $settings->firstWhere('key', 'gitlab_token')?->value;
        $this->gitLabToken = $encryptedToken ? decrypt($encryptedToken) : '';

        // Decrypt the GitHub token when retrieving it
        $encryptedGitHubToken = $settings->firstWhere('key', 'github_token')?->value;
        $this->gitHubToken = $encryptedGitHubToken ? decrypt($encryptedGitHubToken) : '';

        $this->homePage = $settings->firstWhere('key', 'homePage')?->value ?? '';
        $this->googleAnalyticsId = $settings->firstWhere('key', 'google_analytics_id')?->value ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'gitLabToken' => 'nullable|string',
            'gitHubToken' => ['nullable', 'string', 'regex:/^(ghp_|github_pat_)[a-zA-Z0-9_]+$/'],
            'homePage' => 'nullable|string',
            'googleAnalyticsId' => 'nullable|string|regex:/^G-[A-Z0-9]+$/',
        ], [
            'gitHubToken.regex' => 'The GitHub token must be a valid personal access token (starts with ghp_ or github_pat_).',
            'googleAnalyticsId.regex' => 'The Google Analytics ID must be in the format G-XXXXXXXXXX',
        ]);

        if ($validated) {
            // Encrypt the GitLab token before storing it for security
            $tokenValue = ! empty($validated['gitLabToken'])
                ? encrypt($validated['gitLabToken'])
                : null;

            Setting::updateOrCreate(
                ['key' => 'gitlab_token'],
                ['value' => $tokenValue]
            );

            // Encrypt the GitHub token before storing it for security
            $gitHubTokenValue = ! empty($validated['gitHubToken'])
                ? encrypt($validated['gitHubToken'])
                : null;

            Setting::updateOrCreate(
                ['key' => 'github_token'],
                ['value' => $gitHubTokenValue]
            );

            Setting::updateOrCreate(
                ['key' => 'homePage'],
                ['value' => $validated['homePage']]
            );

            Setting::updateOrCreate(
                ['key' => 'google_analytics_id'],
                ['value' => $validated['googleAnalyticsId']]
            );
        }

        $this->success('Settings saved successfully', 'success');
    }

    #[Computed]
    public function pages()
    {
        return Page::all();
    }

    public function render()
    {
        return view('admin::livewire.settings-page');
    }
}
