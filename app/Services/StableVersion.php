<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Picks stable releases out of registry version lists. A stable version
 * is a plain `MAJOR.MINOR.PATCH` (optionally `v`-prefixed) — dev
 * branches and pre-release suffixes (`-beta.1`, `-RC1`) are ignored.
 */
class StableVersion
{
    public const STABLE_VERSION_REGEX = '/^v?\d+\.\d+\.\d+$/';

    public static function isStable(string $version): bool
    {
        return preg_match(self::STABLE_VERSION_REGEX, trim($version)) === 1;
    }

    public static function normalize(string $version): string
    {
        return ltrim(trim($version), 'v');
    }

    /**
     * @param  array<int, mixed>  $versions
     */
    public static function latest(array $versions): ?string
    {
        $stable = array_map(
            self::normalize(...),
            array_filter($versions, fn (mixed $version): bool => is_string($version) && self::isStable($version)),
        );

        if ($stable === []) {
            return null;
        }

        usort($stable, version_compare(...));

        return end($stable);
    }
}
