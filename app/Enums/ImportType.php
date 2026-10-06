<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The remote-triggerable imports a package supports. Each type owns a
 * set of status columns on `packages` so the API can report the last
 * run of each import independently.
 */
enum ImportType: string
{
    case Docs = 'docs';
    case Changelog = 'changelog';

    public function importedAtColumn(): string
    {
        return "{$this->value}_imported_at";
    }

    public function statusColumn(): string
    {
        return "{$this->value}_import_status";
    }

    public function errorColumn(): string
    {
        return "{$this->value}_import_error";
    }
}
