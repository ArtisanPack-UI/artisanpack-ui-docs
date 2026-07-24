<?php

declare(strict_types=1);

namespace Modules\Pages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pages\Page;

class PageRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $page = $this->route('page');
        $pageId = $page instanceof Page ? $page->id : null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pages', 'slug')->ignore($pageId),
            ],
            'content' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'parent' => [
                'nullable',
                'integer',
                Rule::exists('pages', 'id')->where(function ($query) use ($pageId): void {
                    if ($pageId !== null) {
                        $query->where('id', '!=', $pageId);
                    }
                }),
            ],
            'menu_order' => ['nullable', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }
}
