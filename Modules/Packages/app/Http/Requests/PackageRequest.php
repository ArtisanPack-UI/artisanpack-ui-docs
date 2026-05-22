<?php

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'slug' => ['required'],
            'homepage' => ['nullable', 'integer'],
            'wiki_url' => ['required_without:docs_url', 'nullable'],
            'docs_url' => ['required_without:wiki_url', 'nullable'],
            'changelog_url' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        // Only authenticated users can manage packages
        // Additional role/permission checks can be added here if needed
        return $this->user() !== null;
    }
}
