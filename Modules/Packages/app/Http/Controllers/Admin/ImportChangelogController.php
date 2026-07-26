<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportChangelog;
use Illuminate\Http\RedirectResponse;
use Modules\Packages\Http\Requests\ImportChangelogRequest;
use Modules\Packages\Package;

class ImportChangelogController extends Controller
{
    public function __invoke(ImportChangelogRequest $request, Package $package): RedirectResponse
    {
        $validated = $request->validated();

        $package->update(['changelog_url' => $validated['changelog_url']]);

        ImportChangelog::dispatch($package);

        return back()->with('success', 'Changelog import started! This may take a few moments.');
    }
}
