<?php

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentationRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'title'      => [ 'required' ],
			'slug'       => [ 'required' ],
			'parent'     => [ 'nullable', 'integer' ],
			'package_id' => [ 'required', 'exists:packages' ],
			'content'    => [ 'required' ],
		];
	}

	public function authorize(): bool
	{
		return true;
	}
}
