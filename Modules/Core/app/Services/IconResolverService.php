<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use DOMDocument;
use DOMXPath;
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
 *
 * {@see reference()} returns the same resolution as a structured
 * `{raw, set, name, svg}` iconRef for API consumers.
 */
class IconResolverService
{
    /**
     * Font Awesome set prefixes and the family class each maps to.
     * Unknown prefixes fall back to `fas`.
     *
     * @var array<string, string>
     */
    protected const FONT_AWESOME_FAMILIES = [
        'fas' => 'fa-solid',
        'fab' => 'fa-brands',
        'far' => 'fa-regular',
        'fal' => 'fa-light',
        'fad' => 'fa-duotone',
    ];

    /**
     * Elements stripped from custom SVGs before they're rendered or
     * shipped over the API: anything that can run script, embed foreign
     * content, load external CSS, or animate an attribute (e.g. `href`)
     * into something unsafe.
     *
     * @var array<int, string>
     */
    protected const DISALLOWED_SVG_ELEMENTS = [
        'script',
        'foreignobject',
        'iframe',
        'embed',
        'object',
        'style',
        'animate',
        'animatemotion',
        'animatetransform',
        'set',
    ];

    /**
     * Attribute values that may run script or fetch an external resource
     * (`javascript:` URLs, CSS `url()` pointing anywhere but a local `#id`).
     */
    protected const UNSAFE_SVG_ATTRIBUTE_VALUE_REGEX = '/javascript:|url\(\s*[\'"]?\s*(?!#)/i';

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

    /**
     * Resolve an icon identifier into an iconRef for API consumers.
     *
     * `set` / `name` mirror what {@see resolve()} renders: a custom set
     * prefix when its SVG exists on disk, otherwise the Font Awesome set
     * (`fas`, `fab`, …). `svg` carries sanitized markup only for custom
     * sets, since Font Awesome icons are available to every consumer.
     *
     * @return array{raw: string, set: string, name: string, svg: string|null}|null
     */
    public function reference(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $raw = trim($raw);

        if (str_contains($raw, '.')) {
            [$prefix, $name] = explode('.', $raw, 2);

            $svg = $this->resolveCustomIcon($prefix, $name);
            if ($svg !== null) {
                return ['raw' => $raw, 'set' => $prefix, 'name' => $name, 'svg' => $svg];
            }

            $set = array_key_exists($prefix, self::FONT_AWESOME_FAMILIES) ? $prefix : 'fas';

            return ['raw' => $raw, 'set' => $set, 'name' => $name, 'svg' => null];
        }

        if (str_starts_with($raw, 'fa-')) {
            return ['raw' => $raw, ...$this->parseFontAwesomeClass($raw), 'svg' => null];
        }

        return ['raw' => $raw, 'set' => 'fas', 'name' => $raw, 'svg' => null];
    }

    /**
     * Split a raw Font Awesome class string (`fa-brands fa-github`,
     * `fa-star`) into its set prefix and icon name.
     *
     * @return array{set: string, name: string}
     */
    protected function parseFontAwesomeClass(string $classes): array
    {
        $prefixesByFamily = array_flip(self::FONT_AWESOME_FAMILIES);
        $set = 'fas';
        $name = null;

        foreach (preg_split('/\s+/', $classes) ?: [] as $class) {
            if (isset($prefixesByFamily[$class])) {
                $set = $prefixesByFamily[$class];
            } elseif ($name === null && str_starts_with($class, 'fa-')) {
                $name = substr($class, 3);
            }
        }

        return ['set' => $set, 'name' => $name ?? substr($classes, 3)];
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

        $cacheKey = 'icon-resolver:v2:'.$prefix.':'.$name.':'.filemtime($path);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($path): ?string {
            $svg = $this->sanitizeSvg((string) file_get_contents($path));

            return $svg === null ? null : $this->normalizeSvg($svg);
        });
    }

    /**
     * Strip script-capable elements, event-handler attributes,
     * non-fragment `href`s, and script / external-URL attribute values
     * from an SVG. Returns null when the markup
     * isn't a well-formed `<svg>` document.
     */
    protected function sanitizeSvg(string $svg): ?string
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || $document->documentElement?->localName !== 'svg') {
            return null;
        }

        $elements = iterator_to_array((new DOMXPath($document))->query('//*'));

        foreach ($elements as $element) {
            if (in_array(strtolower($element->localName), self::DISALLOWED_SVG_ELEMENTS, true)) {
                $element->parentNode?->removeChild($element);

                continue;
            }

            // Positional keys: `href` and `xlink:href` share a local name
            // and would otherwise collapse into one entry.
            foreach (iterator_to_array($element->attributes, false) as $attribute) {
                $attributeName = strtolower($attribute->localName);
                $isEventHandler = str_starts_with($attributeName, 'on');
                $isExternalHref = $attributeName === 'href' && ! str_starts_with(trim($attribute->value), '#');
                $hasUnsafeValue = preg_match(self::UNSAFE_SVG_ATTRIBUTE_VALUE_REGEX, $attribute->value) === 1;

                if ($isEventHandler || $isExternalHref || $hasUnsafeValue) {
                    $element->removeAttributeNode($attribute);
                }
            }
        }

        return (string) $document->saveXML($document->documentElement);
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
        $family = self::FONT_AWESOME_FAMILIES[$prefix] ?? 'fa-solid';

        return "{$family} fa-{$name}";
    }
}
