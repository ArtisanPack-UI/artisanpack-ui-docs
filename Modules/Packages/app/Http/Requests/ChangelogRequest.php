<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Packages\Changelog;
use Modules\Packages\Package;

class ChangelogRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
        ];
    }

    /**
     * Pin `package_id` to the URL / existing resource:
     *   - Store: nested `/packages/{package}/changelogs` — take from URL.
     *   - Update: `/changelogs/{changelog}` — take from the changelog's
     *     current owner so a `changelogs:write` token can't silently
     *     move a changelog between packages via the PATCH body.
     */
    protected function prepareForValidation(): void
    {
        $package = $this->route('package');

        if ($package instanceof Package) {
            $this->merge(['package_id' => $package->id]);

            return;
        }

        $changelog = $this->route('changelog');

        if ($changelog instanceof Changelog) {
            $this->merge(['package_id' => $changelog->package_id]);
        }
    }

    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor === null) {
            return false;
        }

        $target = $this->route('changelog');

        if ($target instanceof Changelog) {
            return $actor->can('update', $target);
        }

        return $actor->can('create', Changelog::class);
    }
}
