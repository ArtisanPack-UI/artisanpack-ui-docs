<?php

declare(strict_types=1);

namespace Modules\Pages\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Pages\Page;

class PagePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Page $page): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Page $page): bool
    {
        return true;
    }

    public function delete(User $user, Page $page): bool
    {
        return true;
    }

    public function restore(User $user, Page $page): bool
    {
        return true;
    }

    public function forceDelete(User $user, Page $page): bool
    {
        return true;
    }
}
