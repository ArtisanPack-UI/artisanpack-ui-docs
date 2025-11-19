<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Page;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect();

        // Add homepage
        $urls->push([
            'loc' => url('/'),
            'lastmod' => now()->toW3cString(),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ]);

        // Add all pages
        $pages = Page::all();
        foreach ($pages as $page) {
            if ($page->parent) {
                $parentPage = Page::find($page->parent);
                if ($parentPage) {
                    $url = route('page.child', [
                        'parentSlug' => $parentPage->slug,
                        'slug' => $page->slug,
                    ]);
                } else {
                    continue;
                }
            } else {
                $url = route('page.show', ['slug' => $page->slug]);
            }

            $urls->push([
                'loc' => $url,
                'lastmod' => $page->updated_at->toW3cString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ]);
        }

        // Add all documentation pages
        $packages = Package::all();
        foreach ($packages as $package) {
            $docs = Documentation::where('package_id', $package->id)->get();
            foreach ($docs as $doc) {
                $urls->push([
                    'loc' => route('documentation.show', [
                        'package' => $package->slug,
                        'slug' => $doc->slug,
                    ]),
                    'lastmod' => $doc->updated_at->toW3cString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ]);
            }

            // Add changelog page if exists
            if ($package->changelog()) {
                $urls->push([
                    'loc' => route('changelog.show', ['package' => $package->slug]),
                    'lastmod' => $package->updated_at->toW3cString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ]);
            }
        }

        $content = view('sitemap', ['urls' => $urls])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
