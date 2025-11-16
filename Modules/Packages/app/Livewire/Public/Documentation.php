<?php

namespace Modules\Packages\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Documentation as DocumentationModel;
use Modules\Packages\Package;

#[Layout('core::layouts.app')]
class Documentation extends Component
{
    public DocumentationModel $documentation;

    public Package $packageModel;

    public string $title = '';

    public string $content = '';

    public function mount(string $package, string $slug): void
    {
        // Find the package by slug
        $this->packageModel = Package::where('slug', $package)->firstOrFail();

        // Find the documentation by slug and package
        $this->documentation = DocumentationModel::where('slug', $slug)
            ->where('package_id', $this->packageModel->id)
            ->firstOrFail();

        $this->title = $this->documentation->title;
        $this->content = $this->documentation->content;
    }

    public function render()
    {
        return view('packages::livewire.public.documentation');
    }
}
