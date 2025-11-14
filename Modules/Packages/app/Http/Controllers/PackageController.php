<?php

namespace Modules\Packages\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Packages\Http\Requests\PackageRequest;
use Modules\Packages\Http\Resources\PackageResource;
use Modules\Packages\Package;

class PackageController extends Controller
{
	use AuthorizesRequests;

	public function index()
	{
		$this->authorize( 'viewAny', Package::class );

		return PackageResource::collection( Package::all() );
	}

	public function store( PackageRequest $request )
	{
		$this->authorize( 'create', Package::class );

		return new PackageResource( Package::create( $request->validated() ) );
	}

	public function show( Package $package )
	{
		$this->authorize( 'view', $package );

		return new PackageResource( $package );
	}

	public function update( PackageRequest $request, Package $package )
	{
		$this->authorize( 'update', $package );

		$package->update( $request->validated() );

		return new PackageResource( $package );
	}

	public function destroy( Package $package )
	{
		$this->authorize( 'delete', $package );

		$package->delete();

		return response()->json();
	}
}
