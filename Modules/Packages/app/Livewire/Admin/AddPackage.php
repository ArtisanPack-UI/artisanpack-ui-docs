<?php

namespace Modules\Packages\Livewire\Admin;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Package;

#[Layout('admin::layouts.admin')]
class AddPackage extends Component
{
    use Toast;

    public string $name = '';

    public string $slug = '';

    public ?int $home = null;

    public string $wiki_url = '';

    public string $changelog_url = '';

    public string $icon = '';

    public ?string $version = '';

    public ?string $package_registry = null;

    public function addPackage()
    {
        $validated = $this->validate([
            'name' => 'required|string',
            'slug' => 'required|string',
            'home' => 'nullable|integer',
            'wiki_url' => ['required', 'url', 'regex:/^https:\/\/(github\.com|gitlab\.com|raw\.githubusercontent\.com)\//'],
            'changelog_url' => ['required', 'url', 'regex:/^https:\/\/(github\.com|gitlab\.com|raw\.githubusercontent\.com)\//'],
            'icon' => 'nullable|string',
            'version' => 'nullable|string',
            'package_registry' => 'nullable|in:packagist,npm',
        ], [
            'wiki_url.regex' => 'The wiki URL must be a GitHub or GitLab URL.',
            'changelog_url.regex' => 'The changelog URL must be a GitHub or GitLab URL.',
        ]);

        $package = Package::create($validated);

        $this->success('Package added successfully!');

        $this->redirectRoute('dashboard.packages.edit', ['package' => $package->id]);
    }

    public function updatedName()
    {
        $this->slug = strtolower(str_replace(' ', '-', $this->name));
    }

    public function render()
    {
        return view('packages::livewire.admin.add-package');
    }
}
