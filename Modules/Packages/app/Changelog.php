<?php

namespace Modules\Packages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Changelog extends Model
{
	use HasFactory;

	protected $fillable = [
		'content',
		'package_id',
	];

	public function package(): BelongsTo
	{
		return $this->belongsTo( Package::class );
	}
}
