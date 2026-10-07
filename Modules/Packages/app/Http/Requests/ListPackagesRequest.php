<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPackagesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.string' => 'The slug filter must be a single string.',
        ];
    }

    /**
     * Policy authorization happens in the controller via `viewAny`.
     */
    public function authorize(): bool
    {
        return true;
    }
}
