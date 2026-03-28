<?php

namespace Modules\Packages\Livewire\Admin;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Livewire\Admin\Concerns\HasPackageUrlValidation;
use Modules\Packages\Package;

#[Layout('admin::layouts.admin')]
class AddPackage extends Component
{
    use HasPackageUrlValidation;
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
        $validated = $this->validate(array_merge([
            'name' => 'required|string',
            'slug' => 'required|string',
            'home' => 'nullable|integer',
            'icon' => 'nullable|string',
            'version' => 'nullable|string',
            'package_registry' => 'nullable|in:packagist,npm',
        ], $this->packageUrlRules()), $this->packageUrlMessages());

        $package = Package::create($validated);

        $this->success('Package added successfully!');

        $this->redirectRoute('dashboard.packages.edit', ['package' => $package->id]);
    }

    public function updatedName()
    {
        $this->slug = strtolower(str_replace(' ', '-', $this->name));
    }

    public function render(): View
    {
        return view('packages::livewire.admin.add-package');
    }
}
