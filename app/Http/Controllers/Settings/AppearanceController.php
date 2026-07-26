<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    private const ALLOWED_THEMES = ['light', 'dark', 'system'];

    /**
     * Show the appearance settings page.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Settings/Appearance', [
            'theme' => $request->user()->theme_preference ?? 'system',
        ]);
    }

    /**
     * Persist the user's preferred theme.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_THEMES)],
        ]);

        $request->user()->forceFill([
            'theme_preference' => $validated['theme'],
        ])->save();

        return back()->with('status', 'appearance-updated');
    }
}
