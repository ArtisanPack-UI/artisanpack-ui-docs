<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Packages\Changelog;
use Modules\Packages\Http\Requests\ChangelogRequest;
use Modules\Packages\Http\Resources\ChangelogResource;
use Modules\Packages\Package;

/**
 * v1 API surface for changelogs (V2_REFACTOR_PLAN.md §4.2 / §9.6 item #40).
 */
class ChangelogController extends Controller
{
    use AuthorizesRequests;

    public function index(Package $package): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Changelog::class);

        return ChangelogResource::collection(
            Changelog::query()
                ->where('package_id', $package->id)
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function store(ChangelogRequest $request, Package $package): ChangelogResource
    {
        $data = $request->validated();
        $data['package_id'] = $package->id;

        return new ChangelogResource(Changelog::create($data));
    }

    public function update(ChangelogRequest $request, Changelog $changelog): ChangelogResource
    {
        $changelog->update($request->validated());

        return new ChangelogResource($changelog);
    }

    public function destroy(Changelog $changelog): JsonResponse
    {
        $this->authorize('delete', $changelog);

        $changelog->delete();

        return response()->json(status: 204);
    }
}
