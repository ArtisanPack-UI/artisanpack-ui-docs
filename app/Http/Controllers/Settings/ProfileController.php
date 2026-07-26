<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the profile settings page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Profile', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'isVerified' => $user instanceof MustVerifyEmail ? $user->hasVerifiedEmail() : true,
            'status' => $request->session()->get('status'),
        ]);
    }
}
