<?php

namespace Modules\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Setting;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_description',
        'parent',
        'menu_order',
        'icon',
    ];

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
}
