<?php

namespace Modules\Packages\Livewire\Admin\Concerns;

trait HasPackageUrlValidation
{
    /**
     * Get the validation rules for package URL fields.
     *
     * @return array<string, array<int, string>>
     */
    protected function packageUrlRules(): array
    {
        return [
            'wiki_url' => ['required_without:docs_url', 'nullable', 'url', 'regex:/^https:\/\/(github\.com|gitlab\.com|raw\.githubusercontent\.com)\//'],
            'docs_url' => ['required_without:wiki_url', 'nullable', 'url', 'regex:/^https:\/\/github\.com\//'],
            'changelog_url' => ['required', 'url', 'regex:/^https:\/\/(github\.com|gitlab\.com|raw\.githubusercontent\.com)\//'],
        ];
    }

    /**
     * Get the custom validation messages for package URL fields.
     *
     * @return array<string, string>
     */
    protected function packageUrlMessages(): array
    {
        return [
            'wiki_url.regex' => 'The wiki URL must be a GitHub or GitLab URL.',
            'wiki_url.required_without' => 'A wiki URL or docs URL is required.',
            'docs_url.regex' => 'The docs URL must be a GitHub repository URL.',
            'docs_url.required_without' => 'A docs URL or wiki URL is required.',
            'changelog_url.regex' => 'The changelog URL must be a GitHub or GitLab URL.',
        ];
    }
}
