<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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

    private function setPageActiveState(&$pages): void
    {
        foreach ($pages as &$page) {
            if (isset($page['children'])) {
                $this->setPageActiveState($page['children']);
            }

            $page['active'] = $this->isCurrentUrl($page['slug']) || $this->isChildActive($page['children'] ?? []);
        }
    }

    private function setPackageActiveState(&$packages): void
    {
        foreach ($packages as &$package) {
            if (isset($package['documentation'])) {
                $this->setDocActiveState($package['documentation']);
            }

            $package['active'] = (isset($package['homepage']) && $package['homepage']->active) ||
                (isset($package['documentation']) && $this->isChildActive($package['documentation'])) ||
                (isset($package['changelog']) && $package['changelog']->active);
        }
    }

    private function setDocActiveState(&$docs): void
    {
        foreach ($docs as &$doc) {
            if (isset($doc['children'])) {
                $this->setDocActiveState($doc['children']);
            }

            $doc['active'] = $this->isCurrentUrl($doc['slug']) || $this->isChildActive($doc['children'] ?? []);
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
                    $homepage->active = $this->isCurrentUrl($homepage->slug);
                } else {
                    $regularDocs[] = $doc;
                }
            }

            // Get changelog
            $changelog = $package->changelogs()->first();
            if ($changelog) {
                $changelog->active = $this->isCurrentUrl($changelog->slug);
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
