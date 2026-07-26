<?php

namespace Modules\Pages;

use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Core\Setting;
use Modules\Pages\Database\Factories\PageFactory;

class Page extends Model
{
    use HasFactory;
    use HasSeo;

    /*
     * Skip the SEO package's default boot: it calls `static::observe(...)`
     * during trait boot, which under Laravel 13 instantiates the model
     * mid-boot and throws a LogicException. Our sitemap providers pull
     * URLs directly from the models, so we don't need the auto-populated
     * SitemapEntry rows the observer would create.
     */
    public static function bootHasSeo(): void {}

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_description',
        'parent',
        'menu_order',
        'icon',
    ];

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }

    public function parentPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent');
    }

    public function isHomePage(): bool
    {
        return intval(Setting::where('key', 'homePage')->first()->value) === intval($this->id);
    }

    public function getUrl(): string
    {
        if ($this->parent) {
            $parent = static::find($this->parent);

            if ($parent) {
                return route('page.child', [
                    'parentSlug' => $parent->slug,
                    'slug' => $this->slug,
                ]);
            }
        }

        return route('page.show', ['slug' => $this->slug]);
    }

    /*
     * HasSeo defines a `meta_description` accessor that would shadow our
     * real column. Return the stored value instead so admin edit forms,
     * controllers, and factories keep seeing what the user typed.
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
