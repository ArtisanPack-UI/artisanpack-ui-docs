<?php

declare(strict_types=1);

namespace Modules\Packages\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Packages\Http\Requests\PackageRequest;
use Modules\Packages\Package;

class PackagesController extends Controller
{
    public function index(): Response
    {
        $packages = Package::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'version', 'package_registry'])
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'version' => $package->version,
                'package_registry' => $package->package_registry,
                'edit_url' => route('dashboard.packages.edit', $package),
                'destroy_url' => route('dashboard.packages.destroy', $package),
            ])
            ->all();

        return Inertia::render('Packages::Admin/Index', [
            'packages' => $packages,
            'create_url' => route('dashboard.packages.add'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Packages::Admin/Create', [
            'store_url' => route('dashboard.packages.store'),
            'cancel_url' => route('dashboard.packages'),
        ]);
    }

    public function store(PackageRequest $request): RedirectResponse
    {
        $package = Package::create($this->normalize($request->validated()));

        return redirect()
            ->route('dashboard.packages.edit', $package)
            ->with('success', 'Package added successfully!');
    }

    public function edit(Package $package): Response
    {
        return Inertia::render('Packages::Admin/Edit', [
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'homepage' => $package->homepage,
                'wiki_url' => $package->wiki_url ?? '',
                'docs_url' => $package->docs_url ?? '',
                'changelog_url' => $package->changelog_url,
                'icon' => $package->icon ?? '',
                'version' => $package->version ?? '',
                'package_registry' => $package->package_registry,
            ],
            'documentation_options' => $package->documentation()
                ->orderBy('menu_order')
                ->orderBy('title')
                ->get(['id', 'title'])
                ->map(fn ($doc): array => ['id' => $doc->id, 'name' => $doc->title])
                ->all(),
            'update_url' => route('dashboard.packages.update', $package),
            'destroy_url' => route('dashboard.packages.destroy', $package),
            'index_url' => route('dashboard.packages'),
            'documentation_url' => route('dashboard.packages.documentation', $package),
        ]);
    }

    public function update(PackageRequest $request, Package $package): RedirectResponse
    {
        $package->update($this->normalize($request->validated()));

        return redirect()
            ->route('dashboard.packages.edit', $package)
            ->with('success', 'Package updated successfully!');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $package->delete();

        return redirect()
            ->route('dashboard.packages')
            ->with('success', 'Package deleted.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normalize(array $validated): array
    {
        foreach (['wiki_url', 'docs_url', 'icon', 'version'] as $nullable) {
            if (array_key_exists($nullable, $validated) && $validated[$nullable] === '') {
                $validated[$nullable] = null;
            }
        }

        return $validated;
    }
}
