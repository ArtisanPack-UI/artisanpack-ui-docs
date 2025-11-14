<?php

namespace Modules\Packages\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Packages\Changelog;

class ChangelogPolicy
{
	use HandlesAuthorization;

	public function viewAny( User $user ): bool
	{

	}

	public function view( User $user, Changelog $changelog ): bool
	{
	}

	public function create( User $user ): bool
	{
	}

	public function update( User $user, Changelog $changelog ): bool
	{
	}

	public function delete( User $user, Changelog $changelog ): bool
	{
	}

	public function restore( User $user, Changelog $changelog ): bool
	{
	}

	public function forceDelete( User $user, Changelog $changelog ): bool
	{
	}
}
