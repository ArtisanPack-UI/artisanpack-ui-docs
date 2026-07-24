<?php

declare(strict_types=1);

namespace Modules\Pages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPagesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Cap `items` at 500 so a runaway payload can't amplify into thousands
        // of queries. The existence check in the controller doubles as an
        // ownership check, so no per-item `exists` rule is needed.
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
