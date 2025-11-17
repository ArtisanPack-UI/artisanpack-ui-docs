<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Setting;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Page;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('core::partials.main-sidebar', function ($view) {
            // Get pages with hierarchy
            $pages = $this->buildPageHierarchy();
            $this->setPageActiveState($pages);

            // Get packages with documentation
            $packages = $this->buildPackageMenus();
            $this->setPackageActiveState($packages);

            $view->with([
                'sidebarPages' => $pages,
                'sidebarPackages' => $packages,
            ]);
        });
    }

    private function setPageActiveState(&$pages, $parentSlug = null): void
    {
        foreach ($pages as &$page) {
            if (isset($page['children'])) {
                $this->setPageActiveState($page['children'], $page['slug']);
            }

            // Check if THIS specific page is the current URL
            $page['isCurrentPage'] = $parentSlug
                ? $this->isCurrentChildPageUrl($parentSlug, $page['slug'])
                : $this->isCurrentTopLevelPageUrl($page['slug']);

            // Check if this page or any of its children are active
            $page['active'] = $page['isCurrentPage'] || $this->isChildActive($page['children'] ?? []);
        }
    }

    private function setPackageActiveState(&$packages): void
    {
        foreach ($packages as &$package) {
            if (isset($package['documentation'])) {
                $this->setDocActiveState($package['documentation'], $package['slug']);
            }

            $package['active'] = (isset($package['homepage']) && $package['homepage']->active) ||
                (isset($package['documentation']) && $this->isChildActive($package['documentation'])) ||
                (isset($package['changelog']) && $package['changelog']->active);
        }
    }

    private function setDocActiveState(&$docs, string $packageSlug): void
    {
        foreach ($docs as &$doc) {
            if (isset($doc['children'])) {
                $this->setDocActiveState($doc['children'], $packageSlug);
            }

            // Check if THIS specific doc is the current URL
            $doc['isCurrentPage'] = $this->isCurrentDocUrl($packageSlug, $doc['slug']);

            // Check if this doc or any of its children are active
            $doc['active'] = $doc['isCurrentPage'] || $this->isChildActive($doc['children'] ?? []);
        }
    }

    protected function buildPageHierarchy(): array
    {
        $allPages = Page::orderBy('menu_order')
            ->orderBy('id')
            ->get();

        return $this->buildHierarchy($allPages);
    }

    private function isChildActive($children): bool
    {
        foreach ($children as $child) {
            if ($child['active'] || (isset($child['children']) && $this->isChildActive($child['children']))) {
                return true;
            }
        }

        return false;
    }

    private function isCurrentUrl($slug): bool
    {
        if (is_null($slug)) {
            return false;
        }

        return request()->segment(count(request()->segments())) === $slug;
    }

    private function isCurrentDocUrl(string $packageSlug, string $docSlug): bool
    {
        // Check if we're on a documentation page
        if (request()->segment(1) !== 'documentation' || request()->segment(2) !== $packageSlug) {
            return false;
        }

        // Build the full slug path from URL segments (everything after /documentation/{package}/)
        $segments = request()->segments();
        $urlSlugPath = implode('/', array_slice($segments, 2)); // Skip 'documentation' and package name

        // Compare the URL slug path directly with the doc slug
        // (slugs in DB are stored as full paths like "guides/usage")
        return $urlSlugPath === $docSlug;
    }

    private function isCurrentChangelogUrl(string $packageSlug): bool
    {
        // Check if we're on a changelog page: /changelogs/{package}
        return request()->segment(1) === 'changelogs' &&
               request()->segment(2) === $packageSlug;
    }

    private function isCurrentTopLevelPageUrl(string $slug): bool
    {
        // Check if we're on a top-level page: /{slug}
        // Make sure it's not a documentation or changelog page
        $firstSegment = request()->segment(1);

		if (! $firstSegment) {
			$homePage = Setting::where('key', 'homePage')->first()->value ?? 0;
			 if ($homePage !== 0) {
				 $homePageSlug = Page::find($homePage)->slug;

				 if ($homePageSlug === $slug) {
					 return true;
				 }
			 }
		}

        return $firstSegment === $slug &&
               request()->segment(2) === null &&
               ! in_array($firstSegment, ['documentation', 'changelogs', 'dashboard', 'pages', 'api']);
    }

    private function isCurrentChildPageUrl(string $parentSlug, string $slug): bool
    {
        // Check if we're on a child page: /{parentSlug}/{slug}
        return request()->segment(1) === $parentSlug &&
               request()->segment(2) === $slug &&
               request()->segment(3) === null;
    }

    protected function buildHierarchy($items, $parentId = null): array
    {
        $branch = [];

        foreach ($items as $item) {
            $itemParent = $item->parent ?? null;

            if ($itemParent == $parentId) {
                $children = $this->buildHierarchy($items, $item->id);

                $node = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'icon' => $item->icon,
                    'parent' => $item->parent,
                ];

                if (! empty($children)) {
                    $node['children'] = $children;
                }

                $branch[] = $node;
            }
        }

        return $branch;
    }

    protected function buildPackageMenus(): array
    {
        $packages = Package::orderBy('name')->get();
        $packageMenus = [];

        foreach ($packages as $package) {
            $docs = Documentation::where('package_id', $package->id)
                ->orderBy('menu_order')
                ->orderBy('id')
                ->get();

            $homepage = null;
            $changelog = null;
            $regularDocs = [];

            // Separate homepage and changelog
            foreach ($docs as $doc) {
                if ($package->homepage && $doc->id === $package->homepage) {
                    $homepage = $doc;
                    $homepage->active = $this->isCurrentDocUrl($package->slug, $homepage->slug);
                } else {
                    $regularDocs[] = $doc;
                }
            }

            // Get changelog
            $changelog = $package->changelogs()->first();
            if ($changelog) {
                $changelog->active = $this->isCurrentChangelogUrl($package->slug);
            }

            // Build documentation hierarchy
            $docHierarchy = $this->buildDocHierarchy($regularDocs);

            $packageMenus[] = [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'icon' => $package->icon,
                'homepage' => $homepage,
                'documentation' => $docHierarchy,
                'changelog' => $changelog,
            ];
        }

        return $packageMenus;
    }

    protected function buildDocHierarchy($docs, $parentId = null): array
    {
        $branch = [];

        foreach ($docs as $doc) {
            $docParent = $doc->parent ?? null;

            if ($docParent == $parentId) {
                $children = $this->buildDocHierarchy($docs, $doc->id);

                $node = [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'slug' => $doc->slug,
                    'parent' => $doc->parent,
                ];

                if (! empty($children)) {
                    $node['children'] = $children;
                }

                $branch[] = $node;
            }
        }

        return $branch;
    }
}
