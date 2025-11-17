<?php

namespace Modules\Pages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'title'   => [ 'required' ],
			'slug'    => [ 'required' ],
			'content' => [ 'required' ],
			'parent'  => [ 'nullable', 'integer' ],
		];
	}

	public function authorize(): bool
	{
		// Only authenticated users can manage pages
		// Additional role/permission checks can be added here if needed
		return $this->user() !== null;
	}
}
