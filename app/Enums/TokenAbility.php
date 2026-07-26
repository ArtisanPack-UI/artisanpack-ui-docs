<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Bounded allow-list of Sanctum PAT abilities exposed by the remote-admin API.
 *
 * Any token minted for artisanpackui.dev (or any other consumer) must be
 * scoped to one or more of these values. Nothing outside this list may be
 * granted, so leaked tokens cannot escalate beyond their declared scope.
 *
 * Ref: V2_REFACTOR_PLAN.md §8.2.
 */
enum TokenAbility: string
{
    case PackagesRead = 'packages:read';
    case PackagesWrite = 'packages:write';
    case DocsRead = 'docs:read';
    case DocsWrite = 'docs:write';
    case ChangelogsRead = 'changelogs:read';
    case ChangelogsWrite = 'changelogs:write';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $ability): string => $ability->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $ability): array => ['value' => $ability->value, 'label' => $ability->label()],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::PackagesRead => 'Packages: Read',
            self::PackagesWrite => 'Packages: Write',
            self::DocsRead => 'Documentation: Read',
            self::DocsWrite => 'Documentation: Write',
            self::ChangelogsRead => 'Changelogs: Read',
            self::ChangelogsWrite => 'Changelogs: Write',
        };
    }

    public static function isAllowed(string $ability): bool
    {
        return in_array($ability, self::values(), true);
    }
}
