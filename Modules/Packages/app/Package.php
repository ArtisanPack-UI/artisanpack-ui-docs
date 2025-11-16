<?php

namespace Modules\Packages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function documentation(): HasMany {
        return $this->hasMany(Documentation::class);
    }
}
