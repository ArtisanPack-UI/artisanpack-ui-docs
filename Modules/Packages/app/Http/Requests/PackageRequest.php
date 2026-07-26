<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Packages\Package;

class PackageRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'homepage' => ['nullable', 'integer'],
            'wiki_url' => [
                'nullable',
                'required_without:docs_url',
                'url',
                'regex:/^https:\/\/(github\.com|raw\.githubusercontent\.com)\//',
            ],
            'docs_url' => [
                'nullable',
                'required_without:wiki_url',
                'url',
                'regex:/^https:\/\/github\.com\//',
            ],
            'changelog_url' => [
                'required',
                'url',
                'regex:/^https:\/\/(github\.com|raw\.githubusercontent\.com)\//',
            ],
            'icon' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:255'],
            'package_registry' => ['nullable', 'in:packagist,npm'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wiki_url.regex' => 'The wiki URL must be a GitHub URL.',
            'wiki_url.required_without' => 'A wiki URL or docs URL is required.',
            'docs_url.regex' => 'The docs URL must be a GitHub repository URL.',
            'docs_url.required_without' => 'A docs URL or wiki URL is required.',
            'changelog_url.regex' => 'The changelog URL must be a GitHub URL.',
        ];
    }

    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor === null) {
            return false;
        }

        $target = $this->route('package');

        if ($target instanceof Package) {
            return $actor->can('update', $target);
        }

        return $actor->can('create', Package::class);
    }
}
