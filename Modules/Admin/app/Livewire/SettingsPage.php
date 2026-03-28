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

    public bool $hasGitLabToken = false;

    public bool $hasGitHubToken = false;

    public ?string $homePage = '';

    public ?string $googleAnalyticsId = '';

    public function mount(): void
    {
        $settings = Setting::get();

        $encryptedGitLabToken = $settings->firstWhere('key', 'gitlab_token')?->value;
        $this->hasGitLabToken = ! empty($encryptedGitLabToken);

        $encryptedGitHubToken = $settings->firstWhere('key', 'github_token')?->value;
        $this->hasGitHubToken = ! empty($encryptedGitHubToken);

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
            // Only update tokens when a new value is provided
            if (! empty($validated['gitLabToken'])) {
                Setting::updateOrCreate(
                    ['key' => 'gitlab_token'],
                    ['value' => encrypt($validated['gitLabToken'])]
                );
                $this->hasGitLabToken = true;
            }

            if (! empty($validated['gitHubToken'])) {
                Setting::updateOrCreate(
                    ['key' => 'github_token'],
                    ['value' => encrypt($validated['gitHubToken'])]
                );
                $this->hasGitHubToken = true;
            }

            Setting::updateOrCreate(
                ['key' => 'homePage'],
                ['value' => $validated['homePage']]
            );

            Setting::updateOrCreate(
                ['key' => 'google_analytics_id'],
                ['value' => $validated['googleAnalyticsId']]
            );
        }

        // Clear token values from the public properties after saving
        $this->gitLabToken = '';
        $this->gitHubToken = '';

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
