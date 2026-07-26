<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the password settings page.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Settings/Password', [
            'status' => $request->session()->get('status'),
        ]);
    }
}
