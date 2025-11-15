<?php

namespace Modules\Pages\Livewire\Admin;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Pages\Page;

#[Layout('admin::layouts.admin')]
class Pages extends Component
{
	public array $headers = [];

	public function mount() {
		$this->headers = [
			[
				'key' => 'title',
				'label' => 'Title'
			],
			[
				'key' => 'slug',
				'label' => 'Slug'
			]
		];
	}

	#[Computed]
	public function pages() {
		return Page::all();
	}

    public function delete(Page $pageId) {
        $page = Page::where('id', $pageId)->first();
        $page->delete();
    }

    public function render()
    {
        return view('pages::livewire.admin.pages');
    }
}
