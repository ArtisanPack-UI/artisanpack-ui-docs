<?php

declare(strict_types=1);

namespace Modules\Pages\Sitemap;

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use Illuminate\Support\Collection;
use Modules\Pages\Page;

class PageSitemapProvider implements SitemapProviderContract
{
    public function getUrls(): Collection
    {
        return Page::query()
            ->get()
            ->map(function (Page $page): ?array {
                if (! $page->shouldBeInSitemap()) {
                    return null;
                }

                $url = $page->getUrl();

                if ($url === '') {
                    return null;
                }

                return [
                    'loc' => $url,
                    'lastmod' => $page->updated_at?->toW3cString(),
                    'changefreq' => $this->getChangeFrequency(),
                    'priority' => $page->getSitemapPriority(),
                ];
            })
            ->filter()
            ->values();
    }

    public function getChangeFrequency(): string
    {
        return 'weekly';
    }

    public function getPriority(): float
    {
        return 0.8;
    }

    public function getType(): string
    {
        return 'pages';
    }
}
