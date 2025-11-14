<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Setting;

/** @mixin Setting */
class SettingResource extends JsonResource
{
	public function toArray( Request $request ): array
	{
		return [
			'id'         => $this->id,
			'key'        => $this->key,
			'value'      => $this->value,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
		];
	}
}
