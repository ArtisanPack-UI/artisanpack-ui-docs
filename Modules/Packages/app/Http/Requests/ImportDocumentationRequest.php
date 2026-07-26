<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Packages\Package;

class ImportDocumentationRequest extends FormRequest
{
    /**
     * Only the URL the import job will actually use gets validated, so a
     * stale value in the ignored field never blocks the import.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $source = $this->sourceField();

        if ($source === null) {
            return [
                'docs_url' => ['required_without:wiki_url'],
                'wiki_url' => ['required_without:docs_url'],
            ];
        }

        $regex = $source === 'docs_url'
            ? Package::GITHUB_REPO_URL_REGEX
            : Package::GITHUB_URL_REGEX;

        return [
            $source => ['required', 'url', "regex:{$regex}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wiki_url.regex' => 'The wiki URL must be a GitHub URL.',
            'wiki_url.required_without' => 'A docs URL or wiki URL is required to import documentation.',
            'docs_url.regex' => 'The docs URL must be a GitHub repository URL.',
            'docs_url.required_without' => 'A docs URL or wiki URL is required to import documentation.',
        ];
    }

    public function sourceField(): ?string
    {
        return Package::documentationSourceField(
            $this->input('docs_url'),
            $this->input('wiki_url'),
        );
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
