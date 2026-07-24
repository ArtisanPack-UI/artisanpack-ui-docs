<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use ArtisanPackUI\SEO\Contracts\SitemapProviderContract;
use Illuminate\Http\Response;
use XMLWriter;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($this->providers() as $provider) {
            foreach ($provider->getUrls() as $entry) {
                $this->writeUrl($writer, [
                    'loc' => $entry['loc'],
                    'lastmod' => $entry['lastmod'] ?? null,
                    'changefreq' => $entry['changefreq'] ?? $provider->getChangeFrequency(),
                    'priority' => $entry['priority'] ?? $provider->getPriority(),
                ]);
            }
        }

        $writer->endElement();
        $writer->endDocument();

        return response($writer->outputMemory(), 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * @return iterable<SitemapProviderContract>
     */
    protected function providers(): iterable
    {
        $configured = (array) config('seo.sitemap.providers', []);

        foreach ($configured as $class) {
            $provider = app($class);

            if ($provider instanceof SitemapProviderContract) {
                yield $provider;
            }
        }
    }

    /**
     * @param  array{loc: string, lastmod: string|null, changefreq: string, priority: float|string}  $entry
     */
    protected function writeUrl(XMLWriter $writer, array $entry): void
    {
        $writer->startElement('url');
        $writer->writeElement('loc', $entry['loc']);

        if ($entry['lastmod'] !== null && $entry['lastmod'] !== '') {
            $writer->writeElement('lastmod', $entry['lastmod']);
        }

        $writer->writeElement('changefreq', $entry['changefreq']);
        $writer->writeElement('priority', number_format((float) $entry['priority'], 1));
        $writer->endElement();
    }
}
