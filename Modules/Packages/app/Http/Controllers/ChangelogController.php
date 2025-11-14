<?php

namespace Modules\Packages\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Packages\Changelog;
use Modules\Packages\Http\Requests\ChangelogRequest;
use Modules\Packages\Http\Resources\ChangelogResource;

class ChangelogController extends Controller
{
	use AuthorizesRequests;

	public function index()
	{
		$this->authorize( 'viewAny', Changelog::class );

		return ChangelogResource::collection( Changelog::all() );
	}

	public function store( ChangelogRequest $request )
	{
		$this->authorize( 'create', Changelog::class );

		return new ChangelogResource( Changelog::create( $request->validated() ) );
	}

	public function show( Changelog $changelog )
	{
		$this->authorize( 'view', $changelog );

		return new ChangelogResource( $changelog );
	}

	public function update( ChangelogRequest $request, Changelog $changelog )
	{
		$this->authorize( 'update', $changelog );

		$changelog->update( $request->validated() );

		return new ChangelogResource( $changelog );
	}

	public function destroy( Changelog $changelog )
	{
		$this->authorize( 'delete', $changelog );

		$changelog->delete();

		return response()->json();
	}
}
