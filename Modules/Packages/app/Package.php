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

    public function documentation(): HasMany
    {
        return $this->hasMany(Documentation::class);
    }

    public function changelogs(): HasMany
    {
        return $this->hasMany(Changelog::class);
    }

	public function home(): ?Documentation {
		return Documentation::where('id', intval($this->homepage))->first() ?? null;
	}

	public function changelog(): ?Changelog {
		return $this->changelogs()->first() ?? null;
	}
}
