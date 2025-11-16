<?php

namespace Modules\Packages\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Changelog as ChangelogModel;
use Modules\Packages\Package;

#[Layout('core::layouts.app')]
class Changelog extends Component
{
    public ChangelogModel $changelog;

    public Package $packageModel;

    public string $title = '';

    public string $content = '';

    public function mount(string $package): void
    {
        // Find the package by slug
        $this->packageModel = Package::where('slug', $package)->firstOrFail();

        // Find the changelog for this package
        $this->changelog = ChangelogModel::where('package_id', $this->packageModel->id)
            ->firstOrFail();

        $this->title = $this->changelog->title;
        $this->content = $this->changelog->content;
    }

    public function render()
    {
        return view('packages::livewire.public.changelog');
    }
}
