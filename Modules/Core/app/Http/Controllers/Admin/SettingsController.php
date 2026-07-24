<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Http\Requests\SiteSettingsRequest;
use Modules\Core\Setting;
use Modules\Pages\Page;

class SettingsController extends Controller
{
    public function show(): Response
    {
        $this->authorize('viewAny', Setting::class);

        $settings = Setting::all()->keyBy('key');

        $pages = Page::query()
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
            ])
            ->all();

        $homePage = $settings->get('homePage')?->value;

        return Inertia::render('Core::Admin/Settings', [
            'settings' => [
                'home_page' => $homePage !== null && $homePage !== '' ? (int) $homePage : null,
                'google_analytics_id' => $settings->get('google_analytics_id')?->value ?? '',
                'has_github_token' => ! empty($settings->get('github_token')?->value),
                'has_gitlab_token' => ! empty($settings->get('gitlab_token')?->value),
            ],
            'pages' => $pages,
            'update_url' => route('dashboard.settings.update'),
        ]);
    }

    public function update(SiteSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $validated = $request->validated();

        if ($request->has('home_page')) {
            $homePage = $validated['home_page'] ?? null;
            Setting::updateOrCreate(
                ['key' => 'homePage'],
                ['value' => $homePage !== null ? (string) $homePage : ''],
            );
        }

        if ($request->has('google_analytics_id')) {
            Setting::updateOrCreate(
                ['key' => 'google_analytics_id'],
                ['value' => $validated['google_analytics_id'] ?? ''],
            );
        }

        // Tokens are only replaced when a new value is submitted; an empty
        // field means "keep the existing token" so admins can save other
        // fields without re-entering credentials.
        if (! empty($validated['github_token'])) {
            Setting::updateOrCreate(
                ['key' => 'github_token'],
                ['value' => encrypt($validated['github_token'])],
            );
        }

        if (! empty($validated['gitlab_token'])) {
            Setting::updateOrCreate(
                ['key' => 'gitlab_token'],
                ['value' => encrypt($validated['gitlab_token'])],
            );
        }

        return redirect()
            ->route('dashboard.settings')
            ->with('success', 'Settings saved successfully.');
    }
}
