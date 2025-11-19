<?php

namespace Modules\Packages\Livewire\Admin;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

#[Layout('admin::layouts.admin')]
class ManageDocumentation extends Component
{
    use Toast;

    public Package $package;

    public array $documentation = [];

    public function mount(Package $package): void
    {
        $this->package = $package;
        $this->loadDocumentation();
    }

    public function loadDocumentation(): void
    {
        // Get all documentation for this package, ordered by menu_order
        $docs = Documentation::where('package_id', $this->package->id)
            ->orderBy('menu_order')
            ->orderBy('id')
            ->get();

        // Build hierarchical structure
        $this->documentation = $this->buildHierarchy($docs);
    }

    protected function buildHierarchy($docs, $parentId = null): array
    {
        $branch = [];

        foreach ($docs as $doc) {
            $docParent = $doc->parent ?? null;

            if ($docParent == $parentId) {
                $children = $this->buildHierarchy($docs, $doc->id);

                $item = [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'slug' => $doc->slug,
                    'parent' => $doc->parent,
                    'menu_order' => $doc->menu_order,
                ];

                if (! empty($children)) {
                    $item['children'] = $children;
                }

                $branch[] = $item;
            }
        }

        return $branch;
    }

    public function reorderDocumentation(array $orderedIds): void
    {
        // Update menu_order for all items based on their position in the ordered array
        foreach ($orderedIds as $index => $id) {
            Documentation::where('id', $id)
                ->update(['menu_order' => $index]);
        }

        $this->loadDocumentation();
        $this->success('Documentation order updated successfully!');
    }

    public function reorderChildDocumentation(int $parentId, array $orderedIds): void
    {
        // Update menu_order for all child items based on their position in the ordered array
        foreach ($orderedIds as $index => $id) {
            Documentation::where('id', $id)
                ->where('parent', $parentId)
                ->update(['menu_order' => $index]);
        }

        $this->loadDocumentation();
        $this->success('Child documentation order updated successfully!');
    }

    public function render()
    {
        return view('packages::livewire.admin.manage-documentation');
    }
}
