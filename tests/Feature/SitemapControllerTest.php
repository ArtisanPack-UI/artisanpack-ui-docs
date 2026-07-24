<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Modules\Packages\Changelog;
use Modules\Packages\Documentation;
use Modules\Packages\Package;
use Modules\Pages\Page;

function sitemapEntries(string $body): SimpleXMLElement
{
    $xml = simplexml_load_string($body);
    expect($xml)->not->toBeFalse();

    return $xml;
}

function findSitemapEntry(SimpleXMLElement $xml, string $loc): ?SimpleXMLElement
{
    foreach ($xml->url as $entry) {
        if ((string) $entry->loc === $loc) {
            return $entry;
        }
    }

    return null;
}

it('responds with 200 and an application/xml Content-Type', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');
});

it('renders a <urlset> root bound to the sitemap 0.9 schema', function () {
    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    expect($xml->getName())->toBe('urlset');
    expect($xml->getDocNamespaces()[''] ?? null)->toBe('http://www.sitemaps.org/schemas/sitemap/0.9');
});

it('always emits the homepage entry with priority 1.0, weekly changefreq, and now() as lastmod', function () {
    Carbon::setTestNow('2026-07-23 12:00:00');

    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    $home = findSitemapEntry($xml, url('/'));
    expect($home)->not->toBeNull();
    expect((string) $home->priority)->toBe('1.0');
    expect((string) $home->changefreq)->toBe('weekly');
    expect((string) $home->lastmod)->toBe(Carbon::now()->toW3cString());
});

it('emits top-level pages via page.show with priority 0.8 and weekly changefreq', function () {
    $about = Page::create([
        'title' => 'About',
        'slug' => 'about',
        'content' => '',
        'parent' => null,
    ]);

    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    $entry = findSitemapEntry($xml, route('page.show', ['slug' => 'about']));
    expect($entry)->not->toBeNull();
    expect((string) $entry->priority)->toBe('0.8');
    expect((string) $entry->changefreq)->toBe('weekly');
    expect((string) $entry->lastmod)->toBe($about->updated_at->toW3cString());
});

it('emits nested pages via page.child when the parent resolves', function () {
    $parent = Page::create([
        'title' => 'Docs',
        'slug' => 'docs',
        'content' => '',
        'parent' => null,
    ]);
    $child = Page::create([
        'title' => 'Getting Started',
        'slug' => 'getting-started',
        'content' => '',
        'parent' => $parent->id,
    ]);

    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    $entry = findSitemapEntry($xml, route('page.child', ['parentSlug' => 'docs', 'slug' => 'getting-started']));
    expect($entry)->not->toBeNull();
    expect((string) $entry->priority)->toBe('0.8');
    expect((string) $entry->changefreq)->toBe('weekly');
    expect((string) $entry->lastmod)->toBe($child->updated_at->toW3cString());
});

it('silently skips pages whose parent id does not resolve to a real page', function () {
    Page::create([
        'title' => 'Ghost',
        'slug' => 'ghost',
        'content' => '',
        'parent' => 99999,
    ]);

    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    foreach ($xml->url as $entry) {
        expect((string) $entry->loc)->not->toContain('ghost');
    }
});

it('emits documentation URLs via documentation.show with priority 0.8 and weekly changefreq', function () {
    $package = Package::factory()->create(['slug' => 'test-pkg', 'homepage' => null]);
    $doc = Documentation::create([
        'title' => 'Intro',
        'slug' => 'intro',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);

    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    $entry = findSitemapEntry($xml, route('documentation.show', ['package' => 'test-pkg', 'slug' => 'intro']));
    expect($entry)->not->toBeNull();
    expect((string) $entry->priority)->toBe('0.8');
    expect((string) $entry->changefreq)->toBe('weekly');
    expect((string) $entry->lastmod)->toBe($doc->updated_at->toW3cString());
});

it('emits a changelog URL via changelog.show with priority 0.6 and monthly changefreq when the package has a changelog', function () {
    $package = Package::factory()->create(['slug' => 'test-pkg', 'homepage' => null]);
    Changelog::create([
        'title' => '1.0',
        'content' => '# 1.0',
        'package_id' => $package->id,
    ]);

    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    $entry = findSitemapEntry($xml, route('changelog.show', ['package' => 'test-pkg']));
    expect($entry)->not->toBeNull();
    expect((string) $entry->priority)->toBe('0.6');
    expect((string) $entry->changefreq)->toBe('monthly');
    expect((string) $entry->lastmod)->toBe($package->updated_at->toW3cString());
});

it('does not emit a changelog URL for packages that have no changelog', function () {
    Package::factory()->create(['slug' => 'no-changelog', 'homepage' => null]);

    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    expect(findSitemapEntry($xml, route('changelog.show', ['package' => 'no-changelog'])))->toBeNull();
});

it('orders entries as homepage, then pages, then per-package documentation and changelog', function () {
    Carbon::setTestNow('2026-07-23 12:00:00');

    Page::create(['title' => 'About', 'slug' => 'about', 'content' => '', 'parent' => null]);
    $package = Package::factory()->create(['slug' => 'test-pkg', 'homepage' => null]);
    Documentation::create([
        'title' => 'Intro',
        'slug' => 'intro',
        'content' => '',
        'package_id' => $package->id,
        'menu_order' => 0,
    ]);
    Changelog::create(['title' => '1.0', 'content' => '# 1.0', 'package_id' => $package->id]);

    $response = $this->get('/sitemap.xml');
    $xml = sitemapEntries($response->getContent());

    $locs = [];
    foreach ($xml->url as $entry) {
        $locs[] = (string) $entry->loc;
    }

    expect($locs)->toBe([
        url('/'),
        route('page.show', ['slug' => 'about']),
        route('documentation.show', ['package' => 'test-pkg', 'slug' => 'intro']),
        route('changelog.show', ['package' => 'test-pkg']),
    ]);
});
