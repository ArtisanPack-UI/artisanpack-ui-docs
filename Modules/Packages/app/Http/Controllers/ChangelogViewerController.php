<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Services\NavigationService;
use Modules\Core\Services\TableOfContentsService;
use Modules\Packages\Changelog;
use Modules\Packages\Package;

class ChangelogViewerController extends Controller
{
    public function __construct(
        protected NavigationService $navigation,
        protected TableOfContentsService $tableOfContents,
    ) {}

    public function show(string $package): Response
    {
        $packageModel = Package::where('slug', $package)->firstOrFail();

        $changelog = Changelog::where('package_id', $packageModel->id)->firstOrFail();

        $processed = $this->tableOfContents->process($changelog->content, isMarkdown: true);
        $sanitized = kses($processed['content']);

        return Inertia::render('Packages::Changelog/Show', [
            'package' => [
                'name' => $packageModel->name,
                'slug' => $packageModel->slug,
                'version' => $packageModel->version,
            ],
            'changelog' => [
                'title' => $changelog->title,
                'content' => $sanitized,
                'tableOfContents' => $this->tableOfContents->buildNestedStructure($processed['headings']),
            ],
            'navigation' => [
                'pages' => $this->navigation->buildPages(),
                'packages' => $this->navigation->buildPackages(),
            ],
        ]);
    }
}
