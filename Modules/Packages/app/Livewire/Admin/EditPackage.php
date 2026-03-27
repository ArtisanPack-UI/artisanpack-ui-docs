<?php

namespace Modules\Packages\Livewire\Admin;

use App\Jobs\ImportChangelog;
use App\Jobs\ImportWikiDocumentation;
use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Setting;
use Modules\Packages\Package;

#[Layout('admin::layouts.admin')]
class EditPackage extends Component
{
    use Toast;

    public Package $package;

    public string $name = '';

    public string $slug = '';

    public ?int $homepage = null;

    public string $wiki_url = '';

    public string $changelog_url = '';

    public ?string $icon = '';

    public array $pages = [];

    public ?string $version = '';

    public ?string $package_registry = null;

    public function mount(Package $package)
    {
        $this->package = $package;
        $this->name = $package->name;
        $this->slug = $package->slug;
        $this->homepage = $package->homepage;
        $this->wiki_url = $package->wiki_url;
        $this->changelog_url = $package->changelog_url;
        $this->icon = $package->icon;
        $this->pages = $package->documentation()->get()->toArray();
        $this->version = $package->version;
        $this->package_registry = $package->package_registry;
    }

    public function updatePackage()
    {
        $validated = $this->validate([
            'name' => 'required|string',
            'slug' => 'required|string',
            'homepage' => 'nullable|integer',
            'wiki_url' => 'nullable|string',
            'changelog_url' => 'nullable|string',
            'icon' => 'nullable|string',
            'version' => 'nullable|string',
            'package_registry' => 'nullable|in:packagist,npm',
        ]);

        $this->package->update($validated);

        $this->success('Package updated successfully!');
    }

    public function importDocumentation()
    {
        if (empty($this->wiki_url)) {
            $this->error('Wiki URL is required to import documentation.');

            return;
        }

        $encryptedToken = Setting::where('key', 'github_token')->first()?->value;

        // Decrypt the token (it's stored encrypted for security)
        $githubToken = $encryptedToken ? decrypt($encryptedToken) : null;

        if (empty($githubToken)) {
            $this->error('GitHub token not configured in settings.');

            return;
        }

        ImportWikiDocumentation::dispatch($this->package, $githubToken);

        $this->success('Documentation import started! This may take a few moments.');
    }

    public function importChangelog(): void
    {
        if (empty($this->changelog_url)) {
            $this->error('Changelog URL is required to import changelog.');

            return;
        }

        $encryptedToken = Setting::where('key', 'gitlab_token')->first()?->value;

        // Decrypt the token (it's stored encrypted for security)
        $gitlabToken = $encryptedToken ? decrypt($encryptedToken) : null;

        if (empty($gitlabToken)) {
            $this->error('GitLab token not configured in settings.');

            return;
        }

        ImportChangelog::dispatch($this->package, $gitlabToken);

        $this->success('Changelog import started! This may take a few moments.');
    }

    public function updatedName()
    {
        $this->slug = strtolower(str_replace(' ', '-', $this->name));
    }

    public function render()
    {
        return view('packages::livewire.admin.edit-package');
    }
}
