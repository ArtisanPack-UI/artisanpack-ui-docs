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
            'wiki_url' => ['required', 'url', 'regex:/^https:\/\/(github\.com|gitlab\.com|raw\.githubusercontent\.com)\//'],
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
            'changelog_url.regex' => 'The changelog URL must be a GitHub or GitLab URL.',
        ];
    }
}
