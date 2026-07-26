<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Collection;
use Modules\Core\Setting;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Page;

/**
 * Builds the shared public-site navigation trees (pages + packages).
 *
 * Consumed by both the legacy Livewire View::composer that hydrates
 * `core::partials.main-sidebar` and the Inertia controllers that pass
 * the same structure into the React `<MainSidebar>` component. Keeping
 * one source of truth means the two rendering stacks cannot drift while
 * the Livewire → Inertia migration is in flight.
 */
class NavigationService
{
    /**
     * Slug of the page pinned as `homePage` in settings, resolved
     * lazily and cached on the instance so a full sidebar render
     * doesn't fan out into `count(top-level pages) * 2` extra queries.
     *
     * `false` means "not resolved yet"; `null` means "resolved and
     * there is no home page configured".
     */
    protected string|null|false $homePageSlug = false;

    public function __construct(protected IconResolverService $iconResolver = new IconResolverService) {}

    /**
     * Build the full page hierarchy with per-node active state.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildPages(): array
    {
        $pages = $this->buildPageHierarchy();
        $this->setPageActiveState($pages);

        return $pages;
    }

    /**
     * Build the package menu with documentation + changelog entries and
     * per-node active state.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildPackages(): array
    {
        $packages = $this->buildPackageMenus();
        $this->setPackageActiveState($packages);

        return $packages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildPageHierarchy(): array
    {
        $allPages = Page::orderBy('menu_order')
            ->orderBy('id')
            ->get();

        return $this->buildHierarchy($allPages);
    }

    /**
     * @param  Collection<int, Page>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function buildHierarchy(Collection $items, ?int $parentId = null): array
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
                    'icon' => $this->iconResolver->resolve($item->icon),
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

    /**
     * @return array<int, array<string, mixed>>
     */
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
            $regularDocs = [];

            foreach ($docs as $doc) {
                if ($package->homepage && $doc->id === $package->homepage) {
                    $homepage = [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'slug' => $doc->slug,
                        'active' => $this->isCurrentDocUrl($package->slug, $doc->slug),
                    ];
                } else {
                    $regularDocs[] = $doc;
                }
            }

            $changelog = $package->changelogs()->first();
            $changelogEntry = null;

            if ($changelog) {
                $changelogEntry = [
                    'id' => $changelog->id,
                    'title' => $changelog->title ?? 'Changelog',
                    'slug' => $changelog->slug ?? $package->slug,
                    'active' => $this->isCurrentChangelogUrl($package->slug),
                ];
            }

            $packageMenus[] = [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'icon' => $this->iconResolver->resolve($package->icon),
                'homepage' => $homepage,
                'documentation' => $this->buildDocHierarchy($regularDocs, $package->slug),
                'changelog' => $changelogEntry,
            ];
        }

        return $packageMenus;
    }

    /**
     * @param  array<int, Documentation>|Collection<int, Documentation>  $docs
     * @return array<int, array<string, mixed>>
     */
    protected function buildDocHierarchy(iterable $docs, string $packageSlug, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($docs as $doc) {
            $docParent = $doc->parent ?? null;

            if ($docParent == $parentId) {
                $children = $this->buildDocHierarchy($docs, $packageSlug, $doc->id);

                $node = [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'slug' => $doc->slug,
                    'parent' => $doc->parent,
                    'isCurrentPage' => $this->isCurrentDocUrl($packageSlug, $doc->slug),
                ];

                if (! empty($children)) {
                    $node['children'] = $children;
                }

                $node['active'] = $node['isCurrentPage'] || $this->isChildActive($children);

                $branch[] = $node;
            }
        }

        return $branch;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     */
    protected function setPageActiveState(array &$pages, ?string $parentSlug = null): void
    {
        foreach ($pages as &$page) {
            if (isset($page['children'])) {
                $this->setPageActiveState($page['children'], $page['slug']);
            }

            $page['isCurrentPage'] = $parentSlug
                ? $this->isCurrentChildPageUrl($parentSlug, $page['slug'])
                : $this->isCurrentTopLevelPageUrl($page['slug']);

            $page['active'] = $page['isCurrentPage'] || $this->isChildActive($page['children'] ?? []);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $packages
     */
    protected function setPackageActiveState(array &$packages): void
    {
        foreach ($packages as &$package) {
            $homepageActive = isset($package['homepage']['active']) && $package['homepage']['active'];
            $changelogActive = isset($package['changelog']['active']) && $package['changelog']['active'];

            $package['active'] = $homepageActive
                || $this->isChildActive($package['documentation'] ?? [])
                || $changelogActive;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     */
    protected function isChildActive(array $children): bool
    {
        foreach ($children as $child) {
            if (! empty($child['active'])) {
                return true;
            }

            if (! empty($child['children']) && $this->isChildActive($child['children'])) {
                return true;
            }
        }

        return false;
    }

    protected function isCurrentDocUrl(string $packageSlug, string $docSlug): bool
    {
        if (request()->segment(1) !== 'documentation' || request()->segment(2) !== $packageSlug) {
            return false;
        }

        $segments = request()->segments();
        $urlSlugPath = implode('/', array_slice($segments, 2));

        return $urlSlugPath === $docSlug;
    }

    protected function isCurrentChangelogUrl(string $packageSlug): bool
    {
        return request()->segment(1) === 'changelogs'
            && request()->segment(2) === $packageSlug;
    }

    protected function isCurrentTopLevelPageUrl(string $slug): bool
    {
        $firstSegment = request()->segment(1);

        if (! $firstSegment) {
            return $this->resolveHomePageSlug() === $slug;
        }

        return $firstSegment === $slug
            && request()->segment(2) === null
            && ! in_array($firstSegment, ['documentation', 'changelogs', 'dashboard', 'pages', 'api'], true);
    }

    protected function resolveHomePageSlug(): ?string
    {
        if ($this->homePageSlug !== false) {
            return $this->homePageSlug;
        }

        $homePageId = (int) (Setting::where('key', 'homePage')->first()?->value ?? 0);

        return $this->homePageSlug = $homePageId === 0
            ? null
            : Page::find($homePageId)?->slug;
    }

    protected function isCurrentChildPageUrl(string $parentSlug, string $slug): bool
    {
        return request()->segment(1) === $parentSlug
            && request()->segment(2) === $slug
            && request()->segment(3) === null;
    }
}
