<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('auth::layouts.auth')]
class ForgotPassword extends Component
{
    public string $email = '';

    public function render(): View
    {
        return view('auth::livewire.auth.forgot-password');
    }

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}
