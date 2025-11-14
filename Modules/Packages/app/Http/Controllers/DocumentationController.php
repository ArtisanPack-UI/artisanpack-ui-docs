<?php

namespace Modules\Packages\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Packages\Documentation;
use Modules\Packages\Http\Requests\DocumentationRequest;
use Modules\Packages\Http\Resources\DocumentationResource;

class DocumentationController extends Controller
{
	use AuthorizesRequests;

	public function index()
	{
		$this->authorize( 'viewAny', Documentation::class );

		return DocumentationResource::collection( Documentation::all() );
	}

	public function store( DocumentationRequest $request )
	{
		$this->authorize( 'create', Documentation::class );

		return new DocumentationResource( Documentation::create( $request->validated() ) );
	}

	public function show( Documentation $documentation )
	{
		$this->authorize( 'view', $documentation );

		return new DocumentationResource( $documentation );
	}

	public function update( DocumentationRequest $request, Documentation $documentation )
	{
		$this->authorize( 'update', $documentation );

		$documentation->update( $request->validated() );

		return new DocumentationResource( $documentation );
	}

	public function destroy( Documentation $documentation )
	{
		$this->authorize( 'delete', $documentation );

		$documentation->delete();

		return response()->json();
	}
}
