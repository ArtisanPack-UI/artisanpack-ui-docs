<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

class DocumentationRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'parent' => ['nullable', 'integer'],
            'menu_order' => ['nullable', 'integer', 'min:0'],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'content' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Pin `package_id` to the URL / existing resource:
     *   - Store: nested `/packages/{package}/documentation` — take from URL.
     *   - Update: `/documentation/{documentation}` — take from the doc's
     *     current owner so a `docs:write` token can't silently move a doc
     *     between packages by injecting `package_id` into the PATCH body.
     * This also lets PATCH clients omit `package_id` entirely.
     */
    protected function prepareForValidation(): void
    {
        $package = $this->route('package');

        if ($package instanceof Package) {
            $this->merge(['package_id' => $package->id]);

            return;
        }

        $doc = $this->route('documentation');

        if ($doc instanceof Documentation) {
            $this->merge(['package_id' => $doc->package_id]);
        }
    }

    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor === null) {
            return false;
        }

        $target = $this->route('documentation');

        if ($target instanceof Documentation) {
            return $actor->can('update', $target);
        }

        return $actor->can('create', Documentation::class);
    }
}
