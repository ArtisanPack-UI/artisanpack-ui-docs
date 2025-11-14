<?php

namespace Modules\Pages\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Pages\Http\Requests\PageRequest;
use Modules\Pages\Http\Resources\PageResource;
use Modules\Pages\Page;

class PageController extends Controller
{
	use AuthorizesRequests;

	public function index()
	{
		$this->authorize( 'viewAny', Page::class );

		return PageResource::collection( Page::all() );
	}

	public function store( PageRequest $request )
	{
		$this->authorize( 'create', Page::class );

		return new PageResource( Page::create( $request->validated() ) );
	}

	public function show( Page $page )
	{
		$this->authorize( 'view', $page );

		return new PageResource( $page );
	}

	public function update( PageRequest $request, Page $page )
	{
		$this->authorize( 'update', $page );

		$page->update( $request->validated() );

		return new PageResource( $page );
	}

	public function destroy( Page $page )
	{
		$this->authorize( 'delete', $page );

		$page->delete();

		return response()->json();
	}
}
