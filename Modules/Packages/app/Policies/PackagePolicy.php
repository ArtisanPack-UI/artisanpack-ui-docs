<?php

namespace Modules\Packages\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Packages\Package;

class PackagePolicy
{
	use HandlesAuthorization;

	public function viewAny( User $user ): bool
	{

	}

	public function view( User $user, Package $package ): bool
	{
	}

	public function create( User $user ): bool
	{
	}

	public function update( User $user, Package $package ): bool
	{
	}

	public function delete( User $user, Package $package ): bool
	{
	}

	public function restore( User $user, Package $package ): bool
	{
	}

	public function forceDelete( User $user, Package $package ): bool
	{
	}
}
