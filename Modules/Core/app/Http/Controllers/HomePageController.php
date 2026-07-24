<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Services\NavigationService;
use Modules\Core\Services\TableOfContentsService;
use Modules\Core\Setting;
use Modules\Pages\Page;

class HomePageController extends Controller
{
    public function __construct(
        protected NavigationService $navigation,
        protected TableOfContentsService $tableOfContents,
    ) {}

    public function index(): Response
    {
        $homePage = $this->resolveHomePage();

        $title = $homePage?->title ?? '';
        $metaDescription = $homePage?->meta_description ?? '';
        $content = '';
        $tableOfContents = [];

        if ($homePage) {
            $sanitized = kses($homePage->content);
            $processed = $this->tableOfContents->process($sanitized, isMarkdown: false);
            $content = $processed['content'];
            $tableOfContents = $this->tableOfContents->buildNestedStructure($processed['headings']);
        }

        return Inertia::render('Core::Home', [
            'page' => [
                'title' => $title,
                'metaDescription' => $metaDescription,
                'content' => $content,
                'tableOfContents' => $tableOfContents,
            ],
            'navigation' => [
                'pages' => $this->navigation->buildPages(),
                'packages' => $this->navigation->buildPackages(),
            ],
        ]);
    }

    protected function resolveHomePage(): ?Page
    {
        $setting = Setting::where('key', 'homePage')->first();

        if (! $setting || ! $setting->value) {
            return null;
        }

        return Page::find($setting->value);
    }
}
