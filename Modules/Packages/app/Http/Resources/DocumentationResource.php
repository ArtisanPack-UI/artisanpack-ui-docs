<?php

namespace Modules\Packages\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Packages\Documentation;

/** @mixin Documentation */
class DocumentationResource extends JsonResource
{
	public function toArray( Request $request ): array
	{
		return [
			'id'         => $this->id,
			'title'      => $this->title,
			'slug'       => $this->slug,
			'parent'     => $this->parent,
			'content'    => $this->content,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,

			'package_id' => $this->package_id,

			'package' => new PackageResource( $this->whenLoaded( 'package' ) ),
		];
	}
}
