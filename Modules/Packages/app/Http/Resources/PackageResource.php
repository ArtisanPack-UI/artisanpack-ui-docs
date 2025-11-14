<?php

namespace Modules\Packages\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Packages\Package;

/** @mixin Package */
class PackageResource extends JsonResource
{
	public function toArray( Request $request ): array
	{
		return [
			'id'            => $this->id,
			'name'          => $this->name,
			'slug'          => $this->slug,
			'homepage'      => $this->homepage,
			'wiki_url'      => $this->wiki_url,
			'changelog_url' => $this->changelog_url,
			'created_at'    => $this->created_at,
			'updated_at'    => $this->updated_at,
		];
	}
}
