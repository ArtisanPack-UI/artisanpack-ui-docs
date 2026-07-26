<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Modules\Packages\Documentation;
use Modules\Packages\Http\Controllers\DocumentationReorderController;
use Modules\Packages\Http\Requests\DocumentationRequest;
use Modules\Packages\Http\Resources\DocumentationResource;
use Modules\Packages\Package;

/**
 * v1 API surface for documentation (V2_REFACTOR_PLAN.md §4.2 / §9.6 item #39).
 *
 * The reorder endpoint (`POST /packages/{package}/documentation/reorder`)
 * lives in {@see DocumentationReorderController}
 * and is shared with the Inertia admin so menu-order logic exists in
 * exactly one place.
 */
class DocumentationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected AuditLogger $audit) {}

    /**
     * Nested-tree listing of every doc in the package, ordered by
     * `menu_order` within each parent group.
     */
    public function index(Package $package): JsonResponse
    {
        $this->authorize('viewAny', Documentation::class);

        $docs = Documentation::query()
            ->where('package_id', $package->id)
            ->orderBy('menu_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $this->buildTree($docs),
        ]);
    }

    public function store(DocumentationRequest $request, Package $package): DocumentationResource
    {
        $data = $request->validated();
        $data['package_id'] = $package->id;

        $documentation = Documentation::create($data);

        $this->audit->record(AuditLogger::ACTION_CREATED, $documentation, null, $documentation->getAttributes());

        return new DocumentationResource($documentation);
    }

    public function show(Documentation $documentation): DocumentationResource
    {
        $this->authorize('view', $documentation);

        return new DocumentationResource($documentation);
    }

    public function update(DocumentationRequest $request, Documentation $documentation): DocumentationResource
    {
        $original = $documentation->getOriginal();
        $documentation->update($request->validated());

        $this->audit->record(AuditLogger::ACTION_UPDATED, $documentation, $original, $documentation->getAttributes());

        return new DocumentationResource($documentation);
    }

    public function destroy(Documentation $documentation): JsonResponse
    {
        $this->authorize('delete', $documentation);

        $snapshot = $documentation->getOriginal();
        $documentation->delete();

        $this->audit->record(AuditLogger::ACTION_DELETED, $documentation, $snapshot, null);

        return response()->json(status: 204);
    }

    /**
     * Recursively group `$docs` by their `parent` id starting from `$parent`.
     *
     * @param  Collection<int, Documentation>  $docs
     * @return array<int, array<string, mixed>>
     */
    protected function buildTree(Collection $docs, int $parent = 0): array
    {
        return $docs
            ->filter(fn (Documentation $doc): bool => (int) ($doc->parent ?? 0) === $parent)
            ->values()
            ->map(fn (Documentation $doc): array => [
                'id' => $doc->id,
                'title' => $doc->title,
                'slug' => $doc->slug,
                'parent' => (int) ($doc->parent ?? 0),
                'menu_order' => (int) ($doc->menu_order ?? 0),
                'package_id' => $doc->package_id,
                'content' => $doc->content,
                'meta_description' => $doc->meta_description,
                'created_at' => $doc->created_at,
                'updated_at' => $doc->updated_at,
                'children' => $this->buildTree($docs, (int) $doc->id),
            ])
            ->all();
    }
}
