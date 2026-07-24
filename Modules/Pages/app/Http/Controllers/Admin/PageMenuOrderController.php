<?php

declare(strict_types=1);

namespace Modules\Pages\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pages\Http\Requests\ReorderPagesRequest;
use Modules\Pages\Page;

/**
 * Inertia replacement for the Livewire `ManagePageOrder` component
 * (V2_REFACTOR_PLAN.md §9.4 item #30). Renders the menu-order editor
 * for pages and applies drag-and-drop reorders in a single transaction.
 */
class PageMenuOrderController extends Controller
{
    public function index(): Response
    {
        $pages = Page::query()
            ->orderBy('menu_order')
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'parent', 'menu_order']);

        return Inertia::render('Pages::Admin/MenuOrder', [
            'pages' => $pages->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'parent' => $page->parent ?? 0,
                'menu_order' => $page->menu_order ?? 0,
            ])->all(),
            'reorder_url' => route('dashboard.pages.menu-order.reorder'),
            'back_url' => route('dashboard.pages'),
        ]);
    }

    public function reorder(ReorderPagesRequest $request): RedirectResponse|JsonResponse
    {
        /** @var array<int, array{id:int, menu_order:int}> $items */
        $items = $request->validated('items');

        $pageIds = array_column($items, 'id');

        $existingCount = Page::query()
            ->whereIn('id', $pageIds)
            ->count();

        abort_if($existingCount !== count($pageIds), 422, 'One or more pages do not exist.');

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Page::query()
                    ->whereKey($item['id'])
                    ->update(['menu_order' => $item['menu_order']]);
            }
        });

        if ($this->wantsJson($request)) {
            return response()->json(['message' => 'Page order updated.']);
        }

        return redirect()
            ->route('dashboard.pages.menu-order')
            ->with('success', 'Page order updated.');
    }

    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }
}
