<?php

namespace Modules\Pages\Livewire\Admin;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Pages\Page;

#[Layout('admin::layouts.admin')]
class AddPage extends Component
{
	use Toast;

	public string $title = '';
	public string $slug = '';
	public string $content = '';
	public int $parent = 0;
	public int $order = 0;

	public function save()
	{
		$validated = $this->validate([
			'title' => 'required|string',
			'slug' => 'required|string',
			'content' => 'required|string',
			'parent' => 'nullable|integer',
			'order' => 'nullable|integer',
		]);

		$page = Page::create($validated);

		$this->success('Page created successfully.');

        $this->redirect(route('dashboard.pages.edit', [ 'page' => $page->id ]));
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
        return view('pages::livewire.admin.add-page');
    }
}
