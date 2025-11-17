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
		// Only authenticated users can manage documentation
		// Additional role/permission checks can be added here if needed
		return $this->user() !== null;
	}
}
