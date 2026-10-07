<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers\Admin;

use App\Enums\ImportType;
use App\Http\Controllers\Controller;
use App\Jobs\ImportWikiDocumentation;
use Illuminate\Http\RedirectResponse;
use Modules\Packages\Http\Requests\ImportDocumentationRequest;
use Modules\Packages\Package;

class ImportDocumentationController extends Controller
{
    public function __invoke(ImportDocumentationRequest $request, Package $package): RedirectResponse
    {
        $source = $request->sourceField();
        $validated = $request->validated();
        $package->update([$source => $validated[$source]]);

        $attemptId = $package->markImportQueued(ImportType::Docs);

        ImportWikiDocumentation::dispatch($package, $attemptId);

        return back()->with('success', 'Documentation import started! This may take a few moments.');
    }
}
