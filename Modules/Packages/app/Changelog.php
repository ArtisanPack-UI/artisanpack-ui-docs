<?php

namespace Modules\Packages;

use ArtisanPackUI\SEO\Traits\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Changelog extends Model
{
    use HasFactory;
    use HasSeo;

    // See Page::bootHasSeo for why the SEO package's trait boot is skipped.
    public static function bootHasSeo(): void {}

    protected $fillable = [
        'title',
        'content',
        'package_id',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function getUrl(): string
    {
        return route('changelog.show', ['package' => $this->package->slug]);
    }
}
