<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Packages\Http\Requests\PackageRequest;
use Modules\Packages\Http\Resources\PackageResource;
use Modules\Packages\Package;

/**
 * v1 API surface for packages (V2_REFACTOR_PLAN.md §4.2 / §9.6 item #38).
 *
 * Reuses the web layer's {@see PackageRequest} and {@see PackageResource}
 * so validation, authorization, and serialization stay in one place.
 */
class PackageController extends Controller
{
    use AuthorizesRequests;

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Package::class);

        return PackageResource::collection(
            Package::query()->orderBy('name')->get()
        );
    }

    public function store(PackageRequest $request): PackageResource
    {
        return new PackageResource(Package::create($request->validated()));
    }

    public function show(Package $package): PackageResource
    {
        $this->authorize('view', $package);

        return new PackageResource($package);
    }

    public function update(PackageRequest $request, Package $package): PackageResource
    {
        $package->update($request->validated());

        return new PackageResource($package);
    }

    public function destroy(Package $package): JsonResponse
    {
        $this->authorize('delete', $package);

        $package->delete();

        return response()->json(status: 204);
    }
}
