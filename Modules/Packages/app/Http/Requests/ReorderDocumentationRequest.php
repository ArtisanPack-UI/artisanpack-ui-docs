<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderDocumentationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Cap `items` at 500 so a runaway payload can't amplify into thousands
        // of queries. The ownership `count()` in the controller doubles as an
        // existence check, so no per-item `exists` rule is needed.
        return [
            'items' => ['required', 'array', 'max:500'],
            'items.*.id' => ['required', 'integer'],
            'items.*.menu_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }
}
