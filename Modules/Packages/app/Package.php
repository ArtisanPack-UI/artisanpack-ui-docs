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

    /**
     * Matches GitHub content URLs — repo, blob, or raw. Used for wiki and
     * changelog URLs where either github.com or raw.githubusercontent.com
     * is acceptable.
     */
    public const GITHUB_URL_REGEX = '/^https:\/\/(github\.com|raw\.githubusercontent\.com)\//';

    /**
     * Matches GitHub repository URLs only (no raw host). Used for docs_url
     * where we need to walk a repo tree.
     */
    public const GITHUB_REPO_URL_REGEX = '/^https:\/\/github\.com\//';

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

    /**
     * The field the documentation import job reads from, given the current
     * URLs. `docs_url` wins over `wiki_url`; returns null if neither is set.
     * Single source of truth for the priority rule (see also
     * `ImportWikiDocumentation::handle`).
     */
    public static function documentationSourceField(?string $docsUrl, ?string $wikiUrl): ?string
    {
        if (! empty($docsUrl)) {
            return 'docs_url';
        }

        if (! empty($wikiUrl)) {
            return 'wiki_url';
        }

        return null;
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
