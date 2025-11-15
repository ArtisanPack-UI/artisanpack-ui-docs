<?php

namespace Modules\Core\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Setting;
use Modules\Pages\Page;

#[Layout('core::layouts.app')]
class HomePage extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $content = '';

    public function mount(): void
    {
        $homeSetting = Setting::where('key', 'homePage')->first();

        if ($homeSetting && $homeSetting->value) {
            $this->page = Page::find($homeSetting->value);

            if ($this->page) {
                $this->title = $this->page->title;
                $this->content = $this->page->content;
            }
        }
    }

    public function render()
    {
        return view('core::livewire.home-page');
    }
}
