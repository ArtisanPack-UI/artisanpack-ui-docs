<?php

namespace Modules\Packages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\ImportWikiDocumentation;
use Illuminate\Http\JsonResponse;
use Modules\Packages\Package;

class ImportDocumentationController extends Controller
{
    /**
     * Trigger a documentation import for a package.
     *
     * Intended to be called from a GitHub Actions release workflow using an
     * authenticated Sanctum token. Dispatches the import job, which reads from the
     * package's docs_url (preferred) or wiki_url (fallback).
     */
    public function __invoke(Package $package): JsonResponse
    {
        if (empty($package->docs_url) && empty($package->wiki_url)) {
            return response()->json([
                'message' => 'The package does not have a docs URL or wiki URL configured.',
            ], 422);
        }

        ImportWikiDocumentation::dispatch($package);

        return response()->json([
            'message' => 'Documentation import queued.',
            'package' => $package->slug,
            'source' => ! empty($package->docs_url) ? 'docs' : 'wiki',
        ], 202);
    }
}
