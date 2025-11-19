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

    public string $meta_description = '';

    public ?int $parent = 0;

    public ?int $menu_order = 0;

    public ?string $icon = '';

    public $page;

    public function mount(Page $page): void
    {
        $this->page = $page;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->content = $page->content;
        $this->meta_description = $page->meta_description ?? '';
        $this->parent = $page->parent;
        $this->icon = $page->icon;
        $this->menu_order = $page->menu_order ?? 0;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string',
            'slug' => 'required|string',
            'content' => 'required|string',
            'meta_description' => 'nullable|string|max:160',
            'parent' => 'nullable|integer',
            'menu_order' => 'nullable|integer',
            'icon' => 'nullable|string',
        ]);

        $this->page->update($validated);

        $this->success('Page updated successfully.');
    }

    #[Computed]
    public function pages()
    {
        return Page::all();
    }

    public function updatedTitle(): void
    {
        $this->slug = strtolower(str_replace(' ', '-', $this->title));
    }

    public function render()
    {
        return view('pages::livewire.admin.edit-page');
    }
}
