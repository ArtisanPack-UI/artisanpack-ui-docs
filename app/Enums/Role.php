<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => self::Admin->value, 'label' => 'Admin'],
            ['value' => self::Editor->value, 'label' => 'Editor'],
        ];
    }
}
