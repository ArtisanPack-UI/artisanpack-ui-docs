<?php

namespace Modules\Packages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Packages\Database\Factories\PackageFactory;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'homepage',
        'wiki_url',
        'changelog_url',
        'icon',
    ];

    protected static function newFactory()
    {
        return PackageFactory::new();
    }
}
