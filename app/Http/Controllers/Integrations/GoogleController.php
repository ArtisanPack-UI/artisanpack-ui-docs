<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class GoogleController extends Controller
{
    public function show(): Response
    {
        $propertyId = config('analytics-google.reporting.property_id');
        $clientId = config('google.client_id');
        $clientSecret = config('google.client_secret');
        $redirectUri = config('google.redirect_uri');

        return Inertia::render('Integrations/Google', [
            'propertyId' => is_string($propertyId) && $propertyId !== '' ? $propertyId : null,
            'hasCredentials' => is_string($clientId) && $clientId !== ''
                && is_string($clientSecret) && $clientSecret !== ''
                && is_string($redirectUri) && $redirectUri !== '',
        ]);
    }
}
