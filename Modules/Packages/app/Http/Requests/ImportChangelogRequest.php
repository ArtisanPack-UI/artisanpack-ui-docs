<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Packages\Package;

class ImportChangelogRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'changelog_url' => [
                'required',
                'url',
                'regex:'.Package::GITHUB_URL_REGEX,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'changelog_url.required' => 'A changelog URL is required to import a changelog.',
            'changelog_url.regex' => 'The changelog URL must be a GitHub URL.',
        ];
    }

    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('package');

        return $actor !== null
            && $target instanceof Package
            && $actor->can('update', $target);
    }
}
