<?php

namespace Modules\Packages\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Packages\Documentation;

class DocumentationPolicy
{
	use HandlesAuthorization;

	public function viewAny( User $user ): bool
	{

	}

	public function view( User $user, Documentation $documentation ): bool
	{
	}

	public function create( User $user ): bool
	{
	}

	public function update( User $user, Documentation $documentation ): bool
	{
	}

	public function delete( User $user, Documentation $documentation ): bool
	{
	}

	public function restore( User $user, Documentation $documentation ): bool
	{
	}

	public function forceDelete( User $user, Documentation $documentation ): bool
	{
	}
}
