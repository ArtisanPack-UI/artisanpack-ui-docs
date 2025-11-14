<?php

namespace Modules\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
	use HasFactory;

	protected $fillable = [
		'title',
		'slug',
		'content',
		'parent',
	];
}
