<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\InertiaSeo;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Services\NavigationService;
use Modules\Core\Services\TableOfContentsService;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

class DocumentationViewerController extends Controller
{
    public function __construct(
        protected NavigationService $navigation,
        protected TableOfContentsService $tableOfContents,
        protected InertiaSeo $seo,
    ) {}

    public function show(string $package, string $slug): Response
    {
        $packageModel = Package::where('slug', $package)->firstOrFail();

        $doc = Documentation::where('package_id', $packageModel->id)
            ->where('slug', $slug)
            ->firstOrFail();

        $processed = $this->tableOfContents->process($doc->content, isMarkdown: true);
        $sanitized = kses($processed['content']);

        $siblings = $this->siblings($packageModel);
        [$previous, $next] = $this->neighbors($siblings, $doc, $packageModel);

        return Inertia::render('Packages::Documentation/Show', [
            'package' => [
                'name' => $packageModel->name,
                'slug' => $packageModel->slug,
                'version' => $packageModel->version,
            ],
            'doc' => [
                'title' => $doc->title,
                'slug' => $doc->slug,
                'metaDescription' => $doc->meta_description ?? '',
                'content' => $sanitized,
                'tableOfContents' => $this->tableOfContents->buildNestedStructure($processed['headings']),
            ],
            'previous' => $previous,
            'next' => $next,
            'navigation' => [
                'pages' => $this->navigation->buildPages(),
                'packages' => $this->navigation->buildPackages(),
            ],
            'seo' => $this->seo->forModel($doc),
        ]);
    }

    /**
     * @return Collection<int, Documentation>
     */
    protected function siblings(Package $package): Collection
    {
        return Documentation::where('package_id', $package->id)
            ->orderBy('menu_order')
            ->orderBy('id')
            ->get(['id', 'title', 'slug']);
    }

    /**
     * Locate the previous/next docs in the package's ordered list.
     *
     * We reuse the same ordering the sidebar uses (`menu_order`, `id`) so
     * the prev/next links follow the same reading order authors see when
     * editing the menu. The package's configured homepage is skipped: it
     * renders as a pinned card at the top of the sidebar (see
     * {@see NavigationService::buildPackageMenus()}), not as an item in
     * the linear walk, so cycling into it would surprise readers.
     *
     * @param  Collection<int, Documentation>  $siblings
     * @return array{0: array<string, string>|null, 1: array<string, string>|null}
     */
    protected function neighbors(Collection $siblings, Documentation $current, Package $package): array
    {
        $homepageId = $package->homepage ? (int) $package->homepage : null;

        $walk = $siblings
            ->reject(fn (Documentation $doc) => $homepageId !== null && $doc->id === $homepageId)
            ->values();

        $index = $walk->search(fn (Documentation $doc) => $doc->id === $current->id);

        if ($index === false) {
            return [null, null];
        }

        return [
            $this->linkFor($walk->get($index - 1), $package),
            $this->linkFor($walk->get($index + 1), $package),
        ];
    }

    /**
     * @return array<string, string>|null
     */
    protected function linkFor(?Documentation $doc, Package $package): ?array
    {
        if ($doc === null) {
            return null;
        }

        return [
            'title' => $doc->title,
            'url' => route('documentation.show', [
                'package' => $package->slug,
                'slug' => $doc->slug,
            ]),
        ];
    }
}
