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

    public function reorderPages(array $orderedIds): void
    {
        // Update menu_order for all items based on their position in the ordered array
        foreach ($orderedIds as $index => $id) {
            Page::where('id', $id)
                ->update(['menu_order' => $index]);
        }

        $this->loadPages();
        $this->success('Page order updated successfully!');
    }

    public function reorderChildPages(int $parentId, array $orderedIds): void
    {
        // Update menu_order for all child items based on their position in the ordered array
        foreach ($orderedIds as $index => $id) {
            Page::where('id', $id)
                ->where('parent', $parentId)
                ->update(['menu_order' => $index]);
        }

        $this->loadPages();
        $this->success('Child page order updated successfully!');
    }

    public function render()
    {
        return view('pages::livewire.admin.manage-page-order');
    }
}
