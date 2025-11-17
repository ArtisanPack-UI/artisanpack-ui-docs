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
		// Only authenticated users can manage changelogs
		// Additional role/permission checks can be added here if needed
		return $this->user() !== null;
	}
}
