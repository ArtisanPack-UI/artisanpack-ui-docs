<?php

namespace Modules\Pages\Livewire\Admin;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Pages\Page;

#[Layout('admin::layouts.admin')]
class ManagePageOrder extends Component
{
    use Toast;

    public array $pages = [];

    public function mount(): void
    {
        $this->loadPages();
    }

    public function loadPages(): void
    {
        // Get all pages, ordered by menu_order
        $pagesCollection = Page::orderBy('menu_order')
            ->orderBy('id')
            ->get();

        // Build hierarchical structure
        $this->pages = $this->buildHierarchy($pagesCollection);
    }

    protected function buildHierarchy($pages, $parentId = null): array
    {
        $branch = [];

        foreach ($pages as $page) {
            $pageParent = $page->parent ?? null;

            if ($pageParent == $parentId) {
                $children = $this->buildHierarchy($pages, $page->id);

                $item = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'parent' => $page->parent,
                    'menu_order' => $page->menu_order,
                ];

                if (! empty($children)) {
                    $item['children'] = $children;
                }

                $branch[] = $item;
            }
        }

        return $branch;
    }

    public function reorderPages(int $oldIndex, int $newIndex): void
    {
        // Get all top-level pages (not children)
        $pages = Page::whereNull('parent')
            ->orderBy('menu_order')
            ->orderBy('id')
            ->get()
            ->toArray();

        // Validate indices
        if ($oldIndex < 0 || $oldIndex >= count($pages) || $newIndex < 0 || $newIndex >= count($pages)) {
            return;
        }

        // Remove item from old position
        $item = array_splice($pages, $oldIndex, 1)[0];

        // Insert item at new position
        array_splice($pages, $newIndex, 0, [$item]);

        // Update menu_order for all items
        foreach ($pages as $index => $page) {
            Page::where('id', $page['id'])
                ->update(['menu_order' => $index]);
        }

        $this->loadPages();
        $this->success('Page order updated successfully!');
    }

    public function render()
    {
        return view('pages::livewire.admin.manage-page-order');
    }
}
