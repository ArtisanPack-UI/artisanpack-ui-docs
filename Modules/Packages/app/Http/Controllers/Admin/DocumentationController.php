<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

class DocumentationController extends Controller
{
    public function index(Package $package): Response
    {
        $docs = Documentation::query()
            ->where('package_id', $package->id)
            ->orderBy('menu_order')
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'parent', 'menu_order']);

        return Inertia::render('Packages::Admin/Documentation/Manage', [
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
            ],
            'documentation' => $docs->map(fn (Documentation $doc): array => [
                'id' => $doc->id,
                'title' => $doc->title,
                'slug' => $doc->slug,
                'parent' => $doc->parent ?? 0,
                'menu_order' => $doc->menu_order,
            ])->all(),
            'reorder_url' => route('dashboard.packages.documentation.reorder', $package),
            'back_url' => route('dashboard.packages.edit', $package),
        ]);
    }
}
