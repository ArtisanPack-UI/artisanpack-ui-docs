<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coerces incoming analytics beacon payloads to the types the vendor
 * DTO expects.
 *
 * The vendor's `ArtisanPackUI\Analytics\Data\PageViewData` typehints
 * `$screenWidth`, `$screenHeight`, `$viewportWidth`, and
 * `$viewportHeight` as `?string`, but the accompanying
 * `TrackPageViewRequest` validates them as `nullable|integer` and the
 * client tracker sends real integers (`window.screen.width`, etc.).
 * `strict_types=1` on the DTO turns the mismatch into a `TypeError`
 * on every real browser beacon, which the controller catches, logs,
 * and swallows as a silent 204 — the dashboard then reads empty.
 *
 * Coercing width/height to strings here fixes the ingest path without
 * patching the vendor tree. Batched pageviews carry the same fields
 * inside `items[].data`, so those are coerced too.
 *
 * Track upstream at https://github.com/ArtisanPack-UI/analytics/issues
 * — remove this middleware once the DTO accepts the int form.
 */
class CoerceAnalyticsBeaconTypes
{
    /**
     * @var list<string>
     */
    private const DIMENSION_FIELDS = [
        'screen_width',
        'screen_height',
        'viewport_width',
        'viewport_height',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $original = $request->all();
        $normalized = $this->normalize($original);

        if ($normalized !== $original) {
            $request->replace($normalized);
            $request->setJson(new InputBag($normalized));
        }

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload): array
    {
        foreach (self::DIMENSION_FIELDS as $field) {
            if (array_key_exists($field, $payload) && is_int($payload[$field])) {
                $payload[$field] = (string) $payload[$field];
            }
        }

        // The `/batch` endpoint carries the same width/height fields
        // one level down inside every item's `data` payload — coerce
        // recursively so a single batch beacon containing pageviews
        // does not stall on the same TypeError.
        if (isset($payload['items']) && is_array($payload['items'])) {
            $payload['items'] = array_map(function ($item) {
                if (! is_array($item)) {
                    return $item;
                }
                if (isset($item['data']) && is_array($item['data'])) {
                    $item['data'] = $this->normalize($item['data']);
                }

                return $item;
            }, $payload['items']);
        }

        return $payload;
    }
}
