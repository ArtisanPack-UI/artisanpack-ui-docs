<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire\Auth;

use App\Livewire\Actions\Logout as PerformLogout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('auth::layouts.auth')]
class VerifyEmail extends Component
{
    public function render(): View
    {
        return view('auth::livewire.auth.verify-email');
    }

    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()?->hasVerifiedEmail() === true) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()?->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(PerformLogout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}
