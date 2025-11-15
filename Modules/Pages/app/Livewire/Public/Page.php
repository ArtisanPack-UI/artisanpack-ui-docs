<?php

namespace Modules\Pages\Livewire\Public;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Setting;
use Modules\Pages\Page as PageModel;

#[Layout('core::layouts.app')]
class Page extends Component
{
    public PageModel $page;

    public string $title = '';

    public string $content = '';

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
        $this->content = $this->page->content;
    }

    public function render()
    {
        return view('pages::livewire.public.page');
    }
}
