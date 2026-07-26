<?php

declare(strict_types=1);

namespace Modules\Packages\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Packages\Changelog;

class ChangelogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Changelog $changelog): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Changelog $changelog): bool
    {
        return true;
    }

    public function delete(User $user, Changelog $changelog): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Changelog $changelog): bool
    {
        return true;
    }

    public function forceDelete(User $user, Changelog $changelog): bool
    {
        return $user->isAdmin();
    }
}
