<?php

namespace Modules\Packages\Livewire\Admin;

use App\Jobs\ImportChangelog;
use App\Jobs\ImportWikiDocumentation;
use App\Services\WikiServiceFactory;
use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Setting;
use Modules\Packages\Livewire\Admin\Concerns\HasPackageUrlValidation;
use Modules\Packages\Package;

#[Layout('admin::layouts.admin')]
class EditPackage extends Component
{
    use HasPackageUrlValidation;
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
        $validated = $this->validate(array_merge([
            'name' => 'required|string',
            'slug' => 'required|string',
            'homepage' => 'nullable|integer',
            'icon' => 'nullable|string',
            'version' => 'nullable|string',
            'package_registry' => 'nullable|in:packagist,npm',
        ], $this->packageUrlRules()), $this->packageUrlMessages());

        $this->package->update($validated);

        $this->success('Package updated successfully!');
    }

    public function importDocumentation()
    {
        if (empty($this->wiki_url)) {
            $this->error('Wiki URL is required to import documentation.');

            return;
        }

        if (empty($this->resolveGithubToken())) {
            $this->error('GitHub token not configured in settings.');

            return;
        }

        // Persist current form values so the queued job uses the latest URL
        $this->package->update(['wiki_url' => $this->wiki_url]);

        ImportWikiDocumentation::dispatch($this->package);

        $this->success('Documentation import started! This may take a few moments.');
    }

    public function importChangelog(): void
    {
        if (empty($this->changelog_url)) {
            $this->error('Changelog URL is required to import changelog.');

            return;
        }

        if (empty($this->resolveGithubToken())) {
            $this->error('GitHub token not configured in settings.');

            return;
        }

        // Persist current form values so the queued job uses the latest URL
        $this->package->update(['changelog_url' => $this->changelog_url]);

        ImportChangelog::dispatch($this->package);

        $this->success('Changelog import started! This may take a few moments.');
    }

    /**
     * Detect the source platform (GitHub or GitLab) from the wiki URL
     */
    #[Computed]
    public function wikiSource(): ?string
    {
        if (empty($this->wiki_url)) {
            return null;
        }

        try {
            return app(WikiServiceFactory::class)->detectSource($this->wiki_url);
        } catch (\Exception) {
            return null;
        }
    }

    public function updatedName()
    {
        $this->slug = strtolower(str_replace(' ', '-', $this->name));
    }

    /**
     * Resolve the GitHub token from encrypted settings
     */
    protected function resolveGithubToken(): ?string
    {
        $encryptedToken = Setting::where('key', 'github_token')->first()?->value;

        try {
            return $encryptedToken ? decrypt($encryptedToken) : null;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return null;
        }
    }

    public function render(): View
    {
        return view('packages::livewire.admin.edit-package');
    }
}
