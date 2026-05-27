<?php

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PackageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'slug' => ['required'],
            'homepage' => ['nullable', 'integer'],
            'wiki_url' => ['nullable', 'url', 'required_without:docs_url'],
            'docs_url' => ['nullable', 'url', 'required_without:wiki_url'],
            'changelog_url' => ['required'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (empty($this->input('wiki_url')) && empty($this->input('docs_url'))) {
                    $validator->errors()->add(
                        'docs_url',
                        'Either a documentation URL or wiki URL must be provided.'
                    );
                }
            },
        ];
    }

    public function authorize(): bool
    {
        // Only authenticated users can manage packages
        // Additional role/permission checks can be added here if needed
        return $this->user() !== null;
    }
}
