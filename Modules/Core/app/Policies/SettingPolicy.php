<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Setting;

class SettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ?Setting $setting = null): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }
}
