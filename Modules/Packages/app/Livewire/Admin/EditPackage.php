<?php

namespace Modules\Packages\Livewire\Admin;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Package;

#[Layout('admin::layouts.admin')]
class EditPackage extends Component
{
    use Toast;
    public Package $package;
    public string $name = '';
    public string $slug = '';
    public int|null $home = null;
    public string $wiki_url = '';
    public string $changelog_url = '';

    public function mount(Package $package) {
        $this->package = $package;
        $this->name = $package->name;
        $this->slug = $package->slug;
        $this->home = $package->home;
        $this->wiki_url = $package->wiki_url;
        $this->changelog_url = $package->changelog_url;
    }

    public function updatePackage() {
        $validated = $this->validate([
                                         'name' => 'required|string',
                                         'slug' => 'required|string',
                                         'home' => 'nullable|integer',
                                         'wiki_url' => 'nullable|string',
                                         'changelog_url' => 'nullable|string',
                                     ]);

        $this->package->update($validated);

        $this->success('Package updated successfully!');
    }

    public function updatedName() {
        $this->slug = strtolower(str_replace(' ', '-', $this->name));
    }

    public function render()
    {
        return view('packages::livewire.admin.edit-package');
    }
}
