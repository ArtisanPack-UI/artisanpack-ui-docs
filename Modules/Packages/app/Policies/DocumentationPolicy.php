<?php

declare(strict_types=1);

namespace Modules\Packages\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Packages\Documentation;

class DocumentationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Documentation $documentation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Documentation $documentation): bool
    {
        return true;
    }

    public function delete(User $user, Documentation $documentation): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Documentation $documentation): bool
    {
        return true;
    }

    public function forceDelete(User $user, Documentation $documentation): bool
    {
        return $user->isAdmin();
    }
}
