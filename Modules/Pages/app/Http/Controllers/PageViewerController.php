<?php

declare(strict_types=1);

namespace Modules\Pages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\InertiaSeo;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Services\NavigationService;
use Modules\Core\Services\TableOfContentsService;
use Modules\Core\Setting;
use Modules\Pages\Page;

class PageViewerController extends Controller
{
    public function __construct(
        protected NavigationService $navigation,
        protected TableOfContentsService $tableOfContents,
        protected InertiaSeo $seo,
    ) {}

    public function show(string $slug): Response|RedirectResponse
    {
        return $this->render($slug, parentSlug: null);
    }

    /*
     * Laravel injects controller scalar parameters positionally from the
     * URL, so this signature must match the `/{parentSlug}/{slug}` route
     * order — a single `show(...)` with a name-based lookup would receive
     * the values reversed.
     */
    public function showChild(string $parentSlug, string $slug): Response|RedirectResponse
    {
        return $this->render($slug, parentSlug: $parentSlug);
    }

    protected function render(string $slug, ?string $parentSlug): Response|RedirectResponse
    {
        $page = $this->resolvePage($slug, $parentSlug);

        if ($this->isConfiguredHomePage($page)) {
            return redirect()->route('home');
        }

        $sanitized = kses($page->content);
        $processed = $this->tableOfContents->process($sanitized, isMarkdown: false);

        return Inertia::render('Pages::Show', [
            'page' => [
                'title' => $page->title,
                'metaDescription' => $page->meta_description ?? '',
                'content' => $processed['content'],
                'tableOfContents' => $this->tableOfContents->buildNestedStructure($processed['headings']),
                'slug' => $page->slug,
                'parentSlug' => $parentSlug,
                'parentTitle' => $parentSlug ? $this->resolveParentTitle($page) : null,
            ],
            'navigation' => [
                'pages' => $this->navigation->buildPages(),
                'packages' => $this->navigation->buildPackages(),
            ],
            'seo' => $this->seo->forModel($page),
        ]);
    }

    protected function resolvePage(string $slug, ?string $parentSlug): Page
    {
        if ($parentSlug !== null) {
            $parent = Page::where('slug', $parentSlug)->firstOrFail();

            return Page::with('parentPage:id,title')
                ->where('slug', $slug)
                ->where('parent', $parent->id)
                ->firstOrFail();
        }

        return Page::where('slug', $slug)
            ->where(function ($query) {
                $query->whereNull('parent')->orWhere('parent', 0);
            })
            ->firstOrFail();
    }

    protected function isConfiguredHomePage(Page $page): bool
    {
        $setting = Setting::where('key', 'homePage')->first();

        if (! $setting || ! $setting->value) {
            return false;
        }

        return (int) $setting->value === (int) $page->id;
    }

    protected function resolveParentTitle(Page $page): ?string
    {
        return $page->parentPage?->title;
    }
}
