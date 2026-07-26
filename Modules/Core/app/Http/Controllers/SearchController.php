<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\IconResolverService;
use Modules\Packages\Changelog;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Page;

/**
 * JSON search endpoint that backs the React ⌘K SearchOverlay.
 *
 * Reproduces the shape of the legacy `mary-search-open` spotlight but
 * emits icons in the same resolved-icon shape the sidebar consumes —
 * `{type: 'class'|'svg', ...}` — so the React overlay can render them
 * without piping raw Blade component tags through `dangerouslySetInnerHTML`
 * the way the Livewire spotlight did.
 */
class SearchController extends Controller
{
    private const RESULT_LIMIT_PER_TYPE = 10;

    public function __construct(protected IconResolverService $iconResolver) {}

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $results = [
            ...$this->pages($query),
            ...$this->packages($query),
            ...$this->documentation($query),
            ...$this->changelogs($query),
        ];

        return response()->json(['results' => $results]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function pages(string $query): array
    {
        return Page::query()
            ->with('parentPage:id,title,slug')
            ->where(function ($builder) use ($query): void {
                $builder->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->limit(self::RESULT_LIMIT_PER_TYPE)
            ->get()
            ->map(function (Page $page): array {
                $parent = $page->parentPage;

                return [
                    'id' => "page-{$page->id}",
                    'name' => $page->title,
                    'description' => $parent ? "Page · {$parent->title}" : 'Page',
                    'link' => $parent
                        ? route('page.child', ['parentSlug' => $parent->slug, 'slug' => $page->slug])
                        : route('page.show', ['slug' => $page->slug]),
                    'icon' => $this->iconResolver->resolve($page->icon ?: 'fas.file-lines'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function packages(string $query): array
    {
        return Package::query()
            ->where('name', 'like', "%{$query}%")
            ->limit(self::RESULT_LIMIT_PER_TYPE)
            ->get()
            ->map(function (Package $package): ?array {
                $link = $this->packageLink($package);

                if ($link === null) {
                    return null;
                }

                return [
                    'id' => "package-{$package->id}",
                    'name' => $package->name,
                    'description' => 'Package',
                    'link' => $link,
                    'icon' => $this->iconResolver->resolve($package->icon ?: 'fas.cube'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Resolve the public-facing URL for a package result.
     *
     * Prefers the package's configured homepage doc, falls back to the
     * first documentation entry by menu order. Returns null when the
     * package has no publishable docs at all, so a result row that would
     * dead-end on a 404 (or on the auth-guarded admin route) never ships
     * back to the overlay.
     */
    protected function packageLink(Package $package): ?string
    {
        $homePage = $package->home();

        if ($homePage !== null) {
            return route('documentation.show', [
                'package' => $package->slug,
                'slug' => $homePage->slug,
            ]);
        }

        $firstDoc = Documentation::where('package_id', $package->id)
            ->orderBy('menu_order')
            ->orderBy('id')
            ->first(['slug']);

        if ($firstDoc === null) {
            return null;
        }

        return route('documentation.show', [
            'package' => $package->slug,
            'slug' => $firstDoc->slug,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function documentation(string $query): array
    {
        return Documentation::query()
            ->with('package:id,name,slug,icon')
            ->where(function ($builder) use ($query): void {
                $builder->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->limit(self::RESULT_LIMIT_PER_TYPE)
            ->get()
            ->map(function (Documentation $documentation): array {
                $package = $documentation->package;

                return [
                    'id' => "documentation-{$documentation->id}",
                    'name' => $documentation->title,
                    'description' => "Documentation · {$package->name}",
                    'link' => route('documentation.show', [
                        'package' => $package->slug,
                        'slug' => $documentation->slug,
                    ]),
                    'icon' => $this->iconResolver->resolve($package->icon ?: 'fas.book-open'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function changelogs(string $query): array
    {
        return Changelog::query()
            ->with('package:id,name,slug,icon')
            ->where(function ($builder) use ($query): void {
                $builder->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->limit(self::RESULT_LIMIT_PER_TYPE)
            ->get()
            ->map(function (Changelog $changelog): array {
                $package = $changelog->package;

                return [
                    'id' => "changelog-{$changelog->id}",
                    'name' => $changelog->title,
                    'description' => "Changelog · {$package->name}",
                    'link' => route('changelog.show', ['package' => $package->slug]),
                    'icon' => $this->iconResolver->resolve($package->icon ?: 'fas.clipboard-list'),
                ];
            })
            ->all();
    }
}
