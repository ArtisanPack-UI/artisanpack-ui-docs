<?php

declare(strict_types=1);

namespace Modules\Packages\Sitemap;

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use Illuminate\Support\Collection;
use Modules\Packages\Changelog;

class ChangelogSitemapProvider implements SitemapProviderContract
{
    public function getUrls(): Collection
    {
        return Changelog::query()
            ->with('package:id,slug')
            ->get()
            ->map(function (Changelog $changelog): ?array {
                if (! $changelog->package || ! $changelog->shouldBeInSitemap()) {
                    return null;
                }

                return [
                    'loc' => $changelog->getUrl(),
                    'lastmod' => $changelog->updated_at?->toW3cString(),
                    'changefreq' => $this->getChangeFrequency(),
                    'priority' => $changelog->getSitemapPriority(),
                ];
            })
            ->filter()
            ->values();
    }

    public function getChangeFrequency(): string
    {
        return 'monthly';
    }

    public function getPriority(): float
    {
        return 0.6;
    }

    public function getType(): string
    {
        return 'changelogs';
    }
}
