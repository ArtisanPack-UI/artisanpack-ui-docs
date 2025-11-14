<?php

namespace Modules\Pages\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Pages\Page;

/** @mixin Page */
class PageResource extends JsonResource
{
	public function toArray( Request $request ): array
	{
		return [
			'id'         => $this->id,
			'title'      => $this->title,
			'slug'       => $this->slug,
			'content'    => $this->content,
			'parent'     => $this->parent,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
		];
	}
}
