<?php

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangelogRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'content'    => [ 'required' ],
			'package_id' => [ 'required', 'exists:packages' ],
		];
	}

	public function authorize(): bool
	{
		return true;
	}
}
