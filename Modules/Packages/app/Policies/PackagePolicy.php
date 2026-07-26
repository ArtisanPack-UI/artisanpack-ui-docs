<?php

declare(strict_types=1);

namespace Modules\Packages\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Packages\Package;

class PackagePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Package $package): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Package $package): bool
    {
        return true;
    }

    public function delete(User $user, Package $package): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Package $package): bool
    {
        return true;
    }

    public function forceDelete(User $user, Package $package): bool
    {
        return true;
    }
}
