<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : null;

        $passwordRules = [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'confirmed', Password::defaults()];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => $passwordRules,
            'role' => ['required', new Enum(Role::class)],
        ];
    }

    public function authorize(): bool
    {
        $actor = $this->user();

        if ($actor === null) {
            return false;
        }

        $target = $this->route('user');

        if ($target instanceof User) {
            return $actor->can('update', $target);
        }

        return $actor->can('create', User::class);
    }
}
