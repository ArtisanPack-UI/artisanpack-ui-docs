<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Resolves navigation icon identifiers into a shape the React sidebar
 * can render.
 *
 * Supported inputs:
 *
 *  - `ap.<name>`  → inline SVG resolved from a configured icon set
 *                   (see config('artisanpack.icons')); the SVG is
 *                   normalized to `fill="currentColor"` so it inherits
 *                   the active text color in the UI.
 *  - `fas.<name>` → Font Awesome solid class
 *  - `fab.<name>` → Font Awesome brand class
 *  - `far.<name>` → Font Awesome regular class
 *  - `fa-*`       → passes through as a Font Awesome class
 *  - anything else → treated as a Font Awesome solid name (`fa-<value>`)
 *
 * Returns either:
 *  - ['type' => 'svg', 'markup' => '<svg>…</svg>']
 *  - ['type' => 'class', 'class' => 'fa-solid fa-cube']
 *  - null when input is empty
 */
class IconResolverService
{
    /**
     * @return array{type: 'svg', markup: string}|array{type: 'class', class: string}|null
     */
    public function resolve(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        if (str_contains($raw, '.')) {
            [$prefix, $name] = explode('.', $raw, 2);

            $svg = $this->resolveCustomIcon($prefix, $name);
            if ($svg !== null) {
                return ['type' => 'svg', 'markup' => $svg];
            }

            return ['type' => 'class', 'class' => $this->fontAwesomeClass($prefix, $name)];
        }

        if (str_starts_with($raw, 'fa-')) {
            $class = str_contains($raw, ' ') ? $raw : "fa-solid {$raw}";

            return ['type' => 'class', 'class' => $class];
        }

        return ['type' => 'class', 'class' => "fa-solid fa-{$raw}"];
    }

    protected function resolveCustomIcon(string $prefix, string $name): ?string
    {
        $sets = (array) config('artisanpack.icons.sets', []);

        $set = null;
        foreach ($sets as $candidate) {
            if (($candidate['prefix'] ?? null) === $prefix) {
                $set = $candidate;
                break;
            }
        }

        if ($set === null || empty($set['path'])) {
            return null;
        }

        $path = rtrim($set['path'], '/').'/'.$name.'.svg';

        if (! is_file($path)) {
            return null;
        }

        $cacheKey = 'icon-resolver:'.$prefix.':'.$name.':'.filemtime($path);

        return Cache::remember($cacheKey, now()->addDay(), fn () => $this->normalizeSvg((string) file_get_contents($path)));
    }

    protected function normalizeSvg(string $svg): string
    {
        // Strip comments (kept out of shipped markup).
        $svg = (string) preg_replace('/<!--.*?-->/s', '', $svg);

        // Force fill/stroke to currentColor so the icon adapts to
        // text-* utilities on the wrapping element.
        $svg = (string) preg_replace('/\sfill="(?!none)[^"]*"/i', ' fill="currentColor"', $svg);

        // Add fill="currentColor" to the root <svg> when missing.
        if (! preg_match('/<svg[^>]*\sfill=/i', $svg)) {
            $svg = (string) preg_replace('/<svg\b/i', '<svg fill="currentColor"', $svg, 1);
        }

        return trim($svg);
    }

    protected function fontAwesomeClass(string $prefix, string $name): string
    {
        $family = match ($prefix) {
            'fab' => 'fa-brands',
            'far' => 'fa-regular',
            'fal' => 'fa-light',
            'fad' => 'fa-duotone',
            default => 'fa-solid',
        };

        return "{$family} fa-{$name}";
    }
}
