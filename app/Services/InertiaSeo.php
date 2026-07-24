<?php

declare(strict_types=1);

namespace App\Services;

use ArtisanPackUI\SEO\Services\SeoService;
use Illuminate\Database\Eloquent\Model;

class InertiaSeo
{
    public function __construct(protected SeoService $seo) {}

    /**
     * Build the shared SEO payload for an Inertia response.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function forModel(Model $model, array $overrides = []): array
    {
        $data = $this->seo->getAll($model);

        return $this->format(
            title: $data['meta']['title'],
            description: $data['meta']['description'],
            canonical: $data['meta']['canonical'],
            robots: $data['meta']['robots'],
            openGraph: $data['openGraph'],
            twitter: $data['twitterCard'],
            hreflang: $data['hreflang'],
            overrides: $overrides,
        );
    }

    /**
     * Build the shared SEO payload from raw values.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function forData(string $title, ?string $description = null, array $overrides = []): array
    {
        return $this->format(
            title: $this->seo->buildTitle($title),
            description: $description,
            canonical: url()->current(),
            robots: (string) config('seo.defaults.robots', 'index, follow'),
            openGraph: [],
            twitter: [],
            hreflang: [],
            overrides: $overrides,
        );
    }

    /**
     * @param  array<string, mixed>  $openGraph
     * @param  array<string, mixed>  $twitter
     * @param  array<int, array{hreflang: string, href: string}>  $hreflang
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function format(
        string $title,
        ?string $description,
        string $canonical,
        string $robots,
        array $openGraph,
        array $twitter,
        array $hreflang,
        array $overrides,
    ): array {
        $payload = [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'openGraph' => array_filter($openGraph, fn ($v) => $v !== null && $v !== ''),
            'twitter' => array_filter($twitter, fn ($v) => $v !== null && $v !== ''),
            'hreflang' => $hreflang,
            'jsonLd' => [],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
