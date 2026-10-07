<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers;

use App\Enums\ImportType;
use App\Http\Controllers\Controller;
use App\Jobs\ImportChangelog;
use Illuminate\Http\JsonResponse;
use Modules\Packages\Package;

class ImportChangelogController extends Controller
{
    /**
     * Trigger a changelog import for a package.
     *
     * Remote counterpart to the admin "Import changelog" button, called
     * with a Sanctum token carrying the `imports:trigger` ability. Imports
     * from the package's stored changelog_url.
     */
    public function __invoke(Package $package): JsonResponse
    {
        if (empty($package->changelog_url)) {
            return response()->json([
                'message' => 'The package does not have a changelog URL configured.',
            ], 422);
        }

        $attemptId = $package->markImportQueued(ImportType::Changelog);

        ImportChangelog::dispatch($package, $attemptId);

        return response()->json([
            'message' => 'Changelog import queued.',
            'package' => $package->slug,
        ], 202);
    }
}
