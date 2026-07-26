<?php

namespace Modules\Packages;

use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Packages\Database\Factories\PackageFactory;

class Package extends Model
{
    use HasFactory;
    use HasSeo;

    /*
     * See Page::bootHasSeo — the package's default trait boot calls
     * `static::observe(...)`, which throws under Laravel 13 because the
     * model is still booting when observe() constructs a new instance.
     */
    public static function bootHasSeo(): void {}

    protected $fillable = [
        'name',
        'slug',
        'homepage',
        'wiki_url',
        'docs_url',
        'changelog_url',
        'icon',
        'version',
        'docs_imported_at',
        'package_registry',
    ];

    protected function casts(): array
    {
        return [
            'docs_imported_at' => 'datetime',
        ];
    }

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

    public function home(): ?Documentation
    {
        return Documentation::where('id', intval($this->homepage))->first() ?? null;
    }

    public function changelog(): ?Changelog
    {
        return $this->changelogs()->first() ?? null;
    }

    public function getUrl(): string
    {
        $home = $this->home();

        if ($home) {
            return route('documentation.show', [
                'package' => $this->slug,
                'slug' => $home->slug,
            ]);
        }

        return route('documentation.show', [
            'package' => $this->slug,
            'slug' => $this->slug,
        ]);
    }

    public function getSeoTitle(): string
    {
        return $this->name;
    }

    public function needsDocumentationReimport(int $daysThreshold = 7): bool
    {
        if ($this->docs_imported_at === null) {
            return true;
        }

        return $this->docs_imported_at->diffInDays(now()) >= $daysThreshold;
    }

    /**
     * Get the full package name for the registry (Packagist or NPM)
     */
    public function getRegistryPackageName(): ?string
    {
        if ($this->package_registry === null) {
            return null;
        }

        return match ($this->package_registry) {
            'packagist' => "artisanpack-ui/{$this->slug}",
            'npm' => "@artisanpack-ui/{$this->slug}",
            default => null,
        };
    }

    /**
     * Check if this package is on Packagist
     */
    public function isPackagist(): bool
    {
        return $this->package_registry === 'packagist';
    }

    /**
     * Check if this package is on NPM
     */
    public function isNpm(): bool
    {
        return $this->package_registry === 'npm';
    }
}
