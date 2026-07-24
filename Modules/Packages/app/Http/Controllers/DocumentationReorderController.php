<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Packages\Documentation;
use Modules\Packages\Http\Requests\ReorderDocumentationRequest;
use Modules\Packages\Package;

/**
 * Shared reorder endpoint for the Inertia admin (`DocsOrderer`) and the
 * v1 API. Both routes resolve here to keep menu-order logic in one place
 * (V2_REFACTOR_PLAN.md §7.2 / §9.4 item #28).
 *
 * Only `menu_order` is mutated — parent/child structure is sourced from
 * the imported GitHub docs and is not editable in the admin panel.
 */
class DocumentationReorderController extends Controller
{
    public function __invoke(ReorderDocumentationRequest $request, Package $package): RedirectResponse|JsonResponse
    {
        /** @var array<int, array{id:int, menu_order:int}> $items */
        $items = $request->validated('items');

        $docIds = array_column($items, 'id');

        $ownedCount = Documentation::query()
            ->where('package_id', $package->id)
            ->whereIn('id', $docIds)
            ->count();

        abort_if($ownedCount !== count($docIds), 422, 'One or more documentation entries do not belong to this package.');

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Documentation::query()
                    ->whereKey($item['id'])
                    ->update(['menu_order' => $item['menu_order']]);
            }
        });

        if ($this->wantsJson($request)) {
            return response()->json(['message' => 'Documentation order updated.']);
        }

        return redirect()
            ->route('dashboard.packages.documentation', $package)
            ->with('success', 'Documentation order updated.');
    }

    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }
}
