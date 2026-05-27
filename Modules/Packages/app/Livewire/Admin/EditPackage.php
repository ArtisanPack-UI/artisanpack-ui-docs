<?php

namespace Modules\Packages\Livewire\Admin;

use App\Jobs\ImportChangelog;
use App\Jobs\ImportWikiDocumentation;
use App\Services\WikiServiceFactory;
use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Illuminate\Contracts\Encryption\DecryptException;
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

    public string $docs_url = '';

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
        $this->wiki_url = $package->wiki_url ?? '';
        $this->docs_url = $package->docs_url ?? '';
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
        // A docs URL takes priority over the wiki URL when both are present.
        $sourceField = ! empty($this->docs_url) ? 'docs_url' : 'wiki_url';
        $sourceUrl = $this->{$sourceField};

        if (empty($sourceUrl)) {
            $this->error('A docs URL or wiki URL is required to import documentation.');

            return;
        }

        $this->validateOnly($sourceField, $this->packageUrlRules(), $this->packageUrlMessages());

        $token = $this->resolveTokenForUrl($sourceUrl);
        if ($token === null) {
            return;
        }

        // Persist current form values so the queued job uses the latest URLs
        $this->package->update([
            'wiki_url' => $this->wiki_url ?: null,
            'docs_url' => $this->docs_url ?: null,
        ]);

        ImportWikiDocumentation::dispatch($this->package);

        $this->success('Documentation import started! This may take a few moments.');
    }

    public function importChangelog(): void
    {
        if (empty($this->changelog_url)) {
            $this->error('Changelog URL is required to import changelog.');

            return;
        }

        $this->validateOnly('changelog_url', $this->packageUrlRules(), $this->packageUrlMessages());

        $token = $this->resolveTokenForUrl($this->changelog_url);
        if ($token === null) {
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
     * Resolve the appropriate token for the given URL based on detected source
     *
     * Shows an error toast and returns null if the token is not configured.
     */
    protected function resolveTokenForUrl(string $url): ?string
    {
        try {
            $source = app(WikiServiceFactory::class)->detectSource($url);
        } catch (\Exception) {
            $this->error('Unable to detect source platform from URL.');

            return null;
        }

        $settingKey = "{$source}_token";
        $brandName = match ($source) {
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            default => ucfirst($source),
        };

        $encryptedToken = Setting::query()->where('key', $settingKey)->value('value');

        try {
            $token = $encryptedToken ? decrypt($encryptedToken) : null;
        } catch (DecryptException) {
            $token = null;
        }

        if (empty($token)) {
            $this->error("{$brandName} token not configured in settings.");

            return null;
        }

        return $token;
    }

    public function render(): View
    {
        return view('packages::livewire.admin.edit-package');
    }
}
