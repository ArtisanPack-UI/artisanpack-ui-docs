<?php

namespace Modules\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'parent',
    ];

    public function parentPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent');
    }
}
