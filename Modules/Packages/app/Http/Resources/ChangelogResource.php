<?php

namespace Modules\Packages\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Packages\Changelog;

/** @mixin Changelog */
class ChangelogResource extends JsonResource
{
	public function toArray( Request $request ): array
	{
		return [
			'id'         => $this->id,
			'content'    => $this->content,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,

			'package_id' => $this->package_id,

			'package' => new PackageResource( $this->whenLoaded( 'package' ) ),
		];
	}
}
