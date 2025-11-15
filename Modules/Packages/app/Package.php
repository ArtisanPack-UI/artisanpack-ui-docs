<?php

namespace Modules\Packages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
	use HasFactory;

	protected $fillable = [
		'name',
		'slug',
		'homepage',
		'wiki_url',
		'changelog_url',
		'icon'
	];
}
