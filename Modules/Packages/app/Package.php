<?php

namespace Modules\Packages;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
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

    /**
     * Upper bound on the import error message persisted for the API, so a
     * runaway exception message can't bloat every package payload.
     */
    public const IMPORT_ERROR_MAX_LENGTH = 1000;

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
        'docs_import_status',
        'docs_import_error',
        'docs_import_attempt',
        'changelog_imported_at',
        'changelog_import_status',
        'changelog_import_error',
        'changelog_import_attempt',
        'package_registry',
    ];

    protected function casts(): array
    {
        return [
            'docs_imported_at' => 'datetime',
            'docs_import_status' => ImportStatus::class,
            'changelog_imported_at' => 'datetime',
            'changelog_import_status' => ImportStatus::class,
        ];
    }

    /**
     * Bind `{package}` by numeric id as before, and fall back to the slug
     * for non-numeric values so API callers can address a package by
     * either key (e.g. `/api/v1/packages/react`).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>|Relation<static, *, *>  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Builder<static>|Relation<static, *, *>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field === null && is_string($value) && ! ctype_digit($value)) {
            return $query->where('slug', $value);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
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

    /**
     * Mark an import as queued and return the new attempt id, which the
     * dispatched job passes back when it records its outcome.
     */
    public function markImportQueued(ImportType $type): string
    {
        $attemptId = (string) Str::uuid();

        $this->update([
            $type->statusColumn() => ImportStatus::Queued,
            $type->errorColumn() => null,
            $type->attemptColumn() => $attemptId,
        ]);

        return $attemptId;
    }

    /**
     * @param  string|null  $attemptId  The attempt the job was queued as; null skips the staleness check.
     */
    public function markImportSucceeded(ImportType $type, ?string $attemptId = null): bool
    {
        return $this->recordImportOutcome($type, $attemptId, [
            $type->importedAtColumn() => now(),
            $type->statusColumn() => ImportStatus::Succeeded,
            $type->errorColumn() => null,
        ]);
    }

    /**
     * @param  string|null  $attemptId  The attempt the job was queued as; null skips the staleness check.
     */
    public function markImportFailed(ImportType $type, string $error, ?string $attemptId = null): bool
    {
        return $this->recordImportOutcome($type, $attemptId, [
            $type->statusColumn() => ImportStatus::Failed,
            $type->errorColumn() => Str::limit($error, self::IMPORT_ERROR_MAX_LENGTH),
        ]);
    }

    /**
     * Write an import outcome only while `$attemptId` is still the latest
     * queued attempt, so an older job finishing last can't overwrite the
     * result of a newer one. Returns false when the attempt was superseded.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function recordImportOutcome(ImportType $type, ?string $attemptId, array $attributes): bool
    {
        $attributes[$this->getUpdatedAtColumn()] = $this->freshTimestamp();

        $updated = static::query()
            ->whereKey($this->getKey())
            ->when($attemptId !== null, fn ($query) => $query->where($type->attemptColumn(), $attemptId))
            ->update($attributes);

        if ($updated === 0) {
            return false;
        }

        $this->forceFill($attributes)->syncOriginalAttributes(array_keys($attributes));

        return true;
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
