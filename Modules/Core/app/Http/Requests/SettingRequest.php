<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'key'   => [ 'required' ],
			'value' => [ 'required' ],
		];
	}

	public function authorize(): bool
	{
		// Only authenticated users can manage settings
		// Additional role/permission checks can be added here if needed
		return $this->user() !== null;
	}
}
