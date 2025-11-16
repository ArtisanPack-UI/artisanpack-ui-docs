<?php

namespace Modules\Core\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Services\TableOfContentsService;
use Modules\Core\Setting;
use Modules\Pages\Page;

#[Layout('core::layouts.app')]
class HomePage extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $content = '';

    public array $tableOfContents = [];

    public function mount(): void
    {
        $homeSetting = Setting::where('key', 'homePage')->first();

        if ($homeSetting && $homeSetting->value) {
            $this->page = Page::find($homeSetting->value);

            if ($this->page) {
                $this->title = $this->page->title;

                // Sanitize content first with kses
                $sanitizedContent = kses($this->page->content);

                // Process sanitized content and extract table of contents
                $tocService = new TableOfContentsService;
                $processed = $tocService->process($sanitizedContent, isMarkdown: false);

                $this->content = $processed['content'];
                $this->tableOfContents = $tocService->buildNestedStructure($processed['headings']);
            }
        }
    }

    public function render()
    {
        return view('core::livewire.home-page');
    }
}
