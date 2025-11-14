<?php

namespace Modules\Pages\Livewire\Admin;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Pages\Page;

#[Layout('admin::layouts.admin')]
class EditPage extends Component
{
	use Toast;

	public string $title = '';
	public string $slug = '';
	public string $content = '';
	public int $parent = 0;
	public int $order = 0;
	public $page;

	public function mount(Page $page) {
		$this->page = $page;
		$this->title = $page->title;
		$this->slug = $page->slug;
		$this->content = $page->content;
		$this->parent = $page->parent;
	}

	public function save()
	{
		$validated = $this->validate([
										 'title' => 'required|string',
										 'slug' => 'required|string',
										 'content' => 'required|string',
										 'parent' => 'nullable|integer',
										 'order' => 'nullable|integer',
									 ]);

		$this->page->update($validated);

		$this->success('Page updated successfully.');
	}

	#[Computed]
	public function pages() {
		return Page::all();
	}

	public function updatedTitle() {
		$this->slug = strtolower(str_replace(' ', '-', $this->title));
	}
    public function render()
    {
        return view('pages::livewire.admin.edit-page');
    }
}
