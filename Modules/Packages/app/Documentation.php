<?php

namespace Modules\Packages;

use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Documentation extends Model
{
    use HasFactory;
    use HasSeo;

    // See Page::bootHasSeo for why the SEO package's trait boot is skipped.
    public static function bootHasSeo(): void {}

    protected $table = 'documentation';

    protected $fillable = [
        'title',
        'slug',
        'parent',
        'menu_order',
        'package_id',
        'content',
        'meta_description',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isPackageHome()
    {
        return intval($this->package()->homepage) === intval($this->id);
    }

    public function getUrl(): string
    {
        return route('documentation.show', [
            'package' => $this->package->slug,
            'slug' => $this->slug,
        ]);
    }

    /*
     * HasSeo defines a `meta_description` accessor that would shadow our
     * real column. Return the stored value instead.
     */
    public function getMetaDescriptionAttribute(): ?string
    {
        return $this->attributes['meta_description'] ?? null;
    }

    public function getSeoDescription(): ?string
    {
        $stored = $this->attributes['meta_description'] ?? null;

        if ($stored !== null && $stored !== '') {
            return $stored;
        }

        if ($this->content) {
            return Str::limit(strip_tags($this->content), 160);
        }

        return null;
    }
}
