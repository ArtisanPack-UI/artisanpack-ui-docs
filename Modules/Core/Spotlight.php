<?php

namespace Modules\Core;

use Illuminate\Http\Request;
use Modules\Packages\Changelog;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Page;

class Spotlight
{
    public function search(Request $request): array
    {
        $search = $request->get('search', '');

        if (empty($search)) {
            return [];
        }

        return [
            ...$this->pages($search),
            ...$this->packages($search),
            ...$this->documentation($search),
            ...$this->changelogs($search),
        ];
    }

    public function pages(string $search): array
    {
        return Page::query()
            ->where('title', 'like', "%{$search}%")
            ->orWhere('content', 'like', "%{$search}%")
            ->limit(10)
            ->get()
            ->map(function (Page $page) {
                $parent = $page->parentPage;

                return [
                    'name' => $page->title,
                    'description' => $parent ? "Page · {$parent->title}" : 'Page',
                    'link' => $parent
                        ? route('page.child', ['parentSlug' => $parent->slug, 'slug' => $page->slug])
                        : route('page.show', ['slug' => $page->slug]),
                    'icon' => $page->icon ? "<x-icon name=\"{$page->icon}\" class=\"size-11\" />" : '<x-icon name="o-document-text" class="size-11" />',
                ];
            })
            ->toArray();
    }

    public function packages(string $search): array
    {
        return Package::query()
            ->where('name', 'like', "%{$search}%")
            ->limit(10)
            ->get()
            ->map(function (Package $package) {
                $homePage = $package->home();

                return [
                    'name' => $package->name,
                    'description' => 'Package',
                    'link' => $homePage
                        ? route('documentation.show', ['package' => $package->slug, 'slug' => $homePage->slug])
                        : route('packages.show', ['package' => $package->slug]),
                    'icon' => $package->icon ? "<x-icon name=\"{$package->icon}\" class=\"size-11\" />" : '<x-icon name="o-cube" class="size-11" />',
                ];
            })
            ->toArray();
    }

    public function documentation(string $search): array
    {
        return Documentation::query()
            ->with('package')
            ->where('title', 'like', "%{$search}%")
            ->orWhere('content', 'like', "%{$search}%")
            ->limit(10)
            ->get()
            ->map(function (Documentation $documentation) {
                return [
                    'name' => $documentation->title,
                    'description' => "Documentation · {$documentation->package->name}",
                    'link' => route('documentation.show', [
                        'package' => $documentation->package->slug,
                        'slug' => $documentation->slug,
                    ]),
                    'icon' => $documentation->package->icon
                        ? "<x-icon name=\"{$documentation->package->icon}\" class=\"size-11\" />"
                        : '<x-icon name="o-book-open" class="size-11" />',
                ];
            })
            ->toArray();
    }

    public function changelogs(string $search): array
    {
        return Changelog::query()
            ->with('package')
            ->where('title', 'like', "%{$search}%")
            ->orWhere('content', 'like', "%{$search}%")
            ->limit(10)
            ->get()
            ->map(function (Changelog $changelog) {
                return [
                    'name' => $changelog->title,
                    'description' => "Changelog · {$changelog->package->name}",
                    'link' => route('changelog.show', ['package' => $changelog->package->slug]),
                    'icon' => $changelog->package->icon
                        ? "<x-icon name=\"{$changelog->package->icon}\" class=\"size-11\" />"
                        : '<x-icon name="o-clipboard-document-list" class="size-11" />',
                ];
            })
            ->toArray();
    }
}
