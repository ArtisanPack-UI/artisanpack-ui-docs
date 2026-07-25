<?php

declare(strict_types=1);

namespace App\Analytics;

enum AnalyticsSource: string
{
    case Local = 'local';
    case Google = 'google';

    public const SETTING_KEY = 'analytics_dashboard_source';

    public static function default(): self
    {
        return self::Local;
    }

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local (first-party)',
            self::Google => 'Google Analytics',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
