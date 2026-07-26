<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Analytics\AnalyticsSource;
use App\Analytics\AnalyticsSourceResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SourceController extends Controller
{
    public function update(Request $request, AnalyticsSourceResolver $resolver): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', Rule::in(array_keys(AnalyticsSource::options()))],
        ]);

        $source = AnalyticsSource::from($validated['source']);

        if ($source === AnalyticsSource::Google && ! $resolver->isGoogleAvailable()) {
            return back()->with(
                'error',
                'Connect a Google account at Integrations → Google before selecting Google Analytics as the source.',
            );
        }

        $resolver->set($source);

        return back()->with('success', 'Analytics source updated to '.$source->label().'.');
    }
}
