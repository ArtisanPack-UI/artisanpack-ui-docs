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
		return true;
	}
}
