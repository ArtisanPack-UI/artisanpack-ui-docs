<?php

namespace Modules\Packages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documentation extends Model
{
	use HasFactory;

	protected $table = 'documentation';

	protected $fillable = [
		'title',
		'slug',
		'parent',
		'package_id',
		'content',
	];

	public function package(): BelongsTo
	{
		return $this->belongsTo( Package::class );
	}
}
