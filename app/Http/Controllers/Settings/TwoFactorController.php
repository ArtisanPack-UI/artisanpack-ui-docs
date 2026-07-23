<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    /**
     * Show the two-factor authentication settings page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        $enabled = ! is_null($user->two_factor_secret);
        $confirmed = ! is_null($user->two_factor_confirmed_at);

        return Inertia::render('Settings/TwoFactor', [
            'enabled' => $enabled,
            'confirmed' => $confirmed,
            'qrCodeSvg' => ($enabled && ! $confirmed) ? $user->twoFactorQrCodeSvg() : null,
            'secretKey' => ($enabled && ! $confirmed) ? Crypt::decrypt($user->two_factor_secret) : null,
            'recoveryCodes' => $confirmed ? $user->recoveryCodes() : [],
            'status' => $request->session()->get('status'),
        ]);
    }
}
