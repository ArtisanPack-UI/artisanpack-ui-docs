<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Setting;

class SiteSettingsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'home_page' => ['nullable', 'integer', 'exists:pages,id'],
            'gitlab_token' => ['nullable', 'string'],
            'github_token' => ['nullable', 'string', 'regex:/^(ghp_|github_pat_)[a-zA-Z0-9_]+$/'],
            'google_analytics_id' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'github_token.regex' => 'The GitHub token must be a valid personal access token (starts with ghp_ or github_pat_).',
            'google_analytics_id.regex' => 'The Google Analytics ID must be in the format G-XXXXXXXXXX.',
        ];
    }

    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor !== null && $actor->can('update', Setting::class);
    }
}
