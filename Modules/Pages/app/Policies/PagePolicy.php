<?php

namespace Modules\Pages\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Pages\Page;

class PagePolicy
{
	use HandlesAuthorization;

	public function viewAny( User $user ): bool
	{

	}

	public function view( User $user, Page $page ): bool
	{
	}

	public function create( User $user ): bool
	{
	}

	public function update( User $user, Page $page ): bool
	{
	}

	public function delete( User $user, Page $page ): bool
	{
	}

	public function restore( User $user, Page $page ): bool
	{
	}

	public function forceDelete( User $user, Page $page ): bool
	{
	}
}
