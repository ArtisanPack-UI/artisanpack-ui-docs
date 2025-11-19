<?php

namespace Modules\Pages\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Services\TableOfContentsService;
use Modules\Core\Setting;
use Modules\Pages\Page as PageModel;

#[Layout('core::layouts.app')]
class Page extends Component
{
    public PageModel $page;

    public string $title = '';

    public string $content = '';

    public string $metaDescription = '';

    public array $tableOfContents = [];

    public function mount(string $slug, ?string $parentSlug = null): void
    {
        if ($parentSlug) {
            $parent = PageModel::where('slug', $parentSlug)->firstOrFail();
            $this->page = PageModel::where('slug', $slug)
                ->where('parent', $parent->id)
                ->firstOrFail();
        } else {
            $this->page = PageModel::where('slug', $slug)
                ->where(function ($query) {
                    $query->whereNull('parent')->orWhere('parent', 0);
                })
                ->firstOrFail();
        }

        $homePage = Setting::where('key', 'homePage')->first();

        if ($homePage && $homePage->value && $this->page->id == $homePage->value) {
            $this->redirect(route('home'));
        }

        $this->title = $this->page->title;
        $this->metaDescription = $this->page->meta_description ?? '';

        // Sanitize content first with kses
        $sanitizedContent = kses($this->page->content);

        // Process sanitized content and extract table of contents
        $tocService = new TableOfContentsService;
        $processed = $tocService->process($sanitizedContent, isMarkdown: false);

        $this->content = $processed['content'];
        $this->tableOfContents = $tocService->buildNestedStructure($processed['headings']);
    }

    public function render()
    {
        return view('pages::livewire.public.page')
            ->layoutData([
                'title' => $this->title,
                'metaDescription' => $this->metaDescription,
            ]);
    }
}
