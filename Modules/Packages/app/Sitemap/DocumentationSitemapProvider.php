<?php

declare(strict_types=1);

namespace Modules\Packages\Sitemap;

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use Illuminate\Support\Collection;
use Modules\Packages\Documentation;

class DocumentationSitemapProvider implements SitemapProviderContract
{
    public function getUrls(): Collection
    {
        return Documentation::query()
            ->with('package:id,slug')
            ->get()
            ->map(function (Documentation $doc): ?array {
                if (! $doc->package || ! $doc->shouldBeInSitemap()) {
                    return null;
                }

                return [
                    'loc' => $doc->getUrl(),
                    'lastmod' => $doc->updated_at?->toW3cString(),
                    'changefreq' => $this->getChangeFrequency(),
                    'priority' => $doc->getSitemapPriority(),
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
        return 'documentation';
    }
}
