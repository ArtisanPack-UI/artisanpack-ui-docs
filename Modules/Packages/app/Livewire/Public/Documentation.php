<?php

namespace Modules\Packages\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Services\TableOfContentsService;
use Modules\Packages\Documentation as DocumentationModel;
use Modules\Packages\Package;

#[Layout('core::layouts.app')]
class Documentation extends Component
{
    public DocumentationModel $documentation;

    public Package $packageModel;

    public string $title = '';

    public string $content = '';

    public array $tableOfContents = [];

    public string $packageName = '';

    public string $version = '';

    public string $metaDescription = '';

    public function mount(string $package, string $slug): void
    {
        // Find the package by slug
        $this->packageModel = Package::where('slug', $package)->firstOrFail();

        $this->packageName = $this->packageModel->name ?? '';
        $this->version = $this->packageModel->version ?? '';

        // Find the documentation by slug and package
        $this->documentation = DocumentationModel::where('slug', $slug)
            ->where('package_id', $this->packageModel->id)
            ->firstOrFail();

        $this->title = $this->documentation->title;
        $this->metaDescription = $this->documentation->meta_description ?? '';

        // Process content and extract table of contents
        $tocService = new TableOfContentsService;
        $processed = $tocService->process($this->documentation->content, isMarkdown: true);

        // Sanitize HTML content to prevent XSS attacks
        $this->content = kses($processed['content']);
        $this->tableOfContents = $tocService->buildNestedStructure($processed['headings']);
    }

    public function render()
    {
        return view('packages::livewire.public.documentation')
            ->layoutData([
                'title' => $this->title,
                'metaDescription' => $this->metaDescription,
            ]);
    }
}
