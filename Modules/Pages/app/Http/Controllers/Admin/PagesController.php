<?php

declare(strict_types=1);

namespace Modules\Pages\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pages\Http\Requests\PageRequest;
use Modules\Pages\Page;

class PagesController extends Controller
{
    public function index(): Response
    {
        $pages = Page::query()
            ->orderBy('title')
            ->get(['id', 'title', 'slug'])
            ->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'edit_url' => route('dashboard.pages.edit', $page),
                'destroy_url' => route('dashboard.pages.destroy', $page),
            ])
            ->all();

        return Inertia::render('Pages::Admin/Index', [
            'pages' => $pages,
            'create_url' => route('dashboard.pages.add'),
            'menu_order_url' => route('dashboard.pages.menu-order'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Pages::Admin/Create', [
            'parent_options' => $this->parentOptions(),
            'store_url' => route('dashboard.pages.store'),
            'cancel_url' => route('dashboard.pages'),
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = Page::create($this->normalize($request->validated()));

        return redirect()
            ->route('dashboard.pages.edit', $page)
            ->with('success', 'Page created successfully!');
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('Pages::Admin/Edit', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'meta_description' => $page->meta_description ?? '',
                'parent' => $page->parent,
                'menu_order' => $page->menu_order ?? 0,
                'icon' => $page->icon ?? '',
            ],
            'parent_options' => $this->parentOptions($page->id),
            'update_url' => route('dashboard.pages.update', $page),
            'destroy_url' => route('dashboard.pages.destroy', $page),
            'index_url' => route('dashboard.pages'),
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->update($this->normalize($request->validated()));

        return redirect()
            ->route('dashboard.pages.edit', $page)
            ->with('success', 'Page updated successfully!');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()
            ->route('dashboard.pages')
            ->with('success', 'Page deleted.');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    protected function parentOptions(?int $exclude = null): array
    {
        return Page::query()
            ->when($exclude, fn ($query) => $query->whereKeyNot($exclude))
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Page $page): array => ['id' => $page->id, 'name' => $page->title])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normalize(array $validated): array
    {
        foreach (['meta_description', 'icon'] as $nullable) {
            if (array_key_exists($nullable, $validated) && $validated[$nullable] === '') {
                $validated[$nullable] = null;
            }
        }

        if (array_key_exists('parent', $validated) && ($validated['parent'] === '' || $validated['parent'] === 0)) {
            $validated['parent'] = null;
        }

        if (! array_key_exists('menu_order', $validated) || $validated['menu_order'] === '' || $validated['menu_order'] === null) {
            $validated['menu_order'] = 0;
        }

        return $validated;
    }
}
