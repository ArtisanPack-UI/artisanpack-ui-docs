<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
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

    public function __construct(protected AuditLogger $audit) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Package::class);

        return PackageResource::collection(
            Package::query()->orderBy('name')->get()
        );
    }

    public function store(PackageRequest $request): PackageResource
    {
        $package = Package::create($request->validated());

        $this->audit->record(AuditLogger::ACTION_CREATED, $package, null, $package->getAttributes());

        return new PackageResource($package);
    }

    public function show(Package $package): PackageResource
    {
        $this->authorize('view', $package);

        return new PackageResource($package);
    }

    public function update(PackageRequest $request, Package $package): PackageResource
    {
        $original = $package->getOriginal();
        $package->update($request->validated());

        $this->audit->record(AuditLogger::ACTION_UPDATED, $package, $original, $package->getAttributes());

        return new PackageResource($package);
    }

    public function destroy(Package $package): JsonResponse
    {
        $this->authorize('delete', $package);

        $snapshot = $package->getOriginal();
        $package->delete();

        $this->audit->record(AuditLogger::ACTION_DELETED, $package, $snapshot, null);

        return response()->json(status: 204);
    }
}
