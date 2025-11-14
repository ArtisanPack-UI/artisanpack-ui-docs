<?php

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'name'          => [ 'required' ],
			'slug'          => [ 'required' ],
			'homepage'      => [ 'nullable', 'integer' ],
			'wiki_url'      => [ 'required' ],
			'changelog_url' => [ 'required' ],
		];
	}

	public function authorize(): bool
	{
		return true;
	}
}
