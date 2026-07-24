<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Http\Controllers\Controller;
use ArtisanPackUI\Privacy\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __construct(protected VerificationService $verifier) {}

    public function show(Request $request, string $token): Response
    {
        $dataRequest = $this->verifier->findByToken($token);

        // Uniform 404 for unknown OR expired tokens so the page cannot be
        // used as an existence oracle for probing valid-but-expired links.
        abort_if($dataRequest === null, 404, __('This verification link is no longer valid.'));

        return Inertia::render('Privacy/Verify', [
            'token' => $token,
            'data_request' => [
                'id' => $dataRequest->id,
                'type' => $dataRequest->type,
                'status' => $dataRequest->status,
                'created_at' => $dataRequest->created_at?->toIso8601String(),
            ],
            'expired' => $this->verifier->isExpired($dataRequest),
            'status_message' => $request->session()->get('status'),
        ]);
    }

    public function verify(Request $request, string $token): RedirectResponse
    {
        $dataRequest = $this->verifier->findByToken($token);

        abort_if($dataRequest === null, 404, __('This verification link is no longer valid.'));

        if ($this->verifier->isExpired($dataRequest)) {
            abort(404, __('This verification link is no longer valid.'));
        }

        $confirmed = $this->verifier->confirm(
            $dataRequest,
            config('artisanpack.privacy.data_requests.verification_method', 'email'),
        );

        return redirect()
            ->route('privacy.verification.show', ['token' => $token])
            ->with('status', $confirmed
                ? __('Your identity has been verified and your request is now being processed.')
                : __('This request has already been verified or is no longer pending.'),
            );
    }
}
