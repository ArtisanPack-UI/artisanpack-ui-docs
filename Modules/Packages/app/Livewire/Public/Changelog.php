<?php

namespace Modules\Packages\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Services\TableOfContentsService;
use Modules\Packages\Changelog as ChangelogModel;
use Modules\Packages\Package;

#[Layout('core::layouts.app')]
class Changelog extends Component
{
    public ChangelogModel $changelog;

    public Package $packageModel;

    public string $title = '';

    public string $content = '';

    public array $tableOfContents = [];
	public string $packageName = '';
	public string $version = '';

    public function mount(string $package): void
    {
        // Find the package by slug
        $this->packageModel = Package::where('slug', $package)->firstOrFail();

		$this->packageName = $this->packageModel->name ?? '';
		$this->version = $this->packageModel->version ?? '';

        // Find the changelog for this package
        $this->changelog = ChangelogModel::where('package_id', $this->packageModel->id)
            ->firstOrFail();

        $this->title = $this->changelog->title;

        // Process content and extract table of contents
        $tocService = new TableOfContentsService;
        $processed = $tocService->process($this->changelog->content, isMarkdown: true);

        // Sanitize HTML content to prevent XSS attacks
        $this->content = kses($processed['content']);
        $this->tableOfContents = $tocService->buildNestedStructure($processed['headings']);
    }

    public function render()
    {
        return view('packages::livewire.public.changelog');
    }
}
