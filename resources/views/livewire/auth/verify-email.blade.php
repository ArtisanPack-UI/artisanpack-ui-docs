<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="mt-4 flex flex-col gap-6">
    <x-artisanpack-text class="text-center">
        {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
    </x-artisanpack-text>

    @if (session('status') == 'verification-link-sent')
        <x-artisanpack-text class="text-center font-medium !dark:text-green-400 !text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </x-artisanpack-text>
    @endif

    <div class="flex flex-col items-center justify-between space-y-3">
        <x-artisanpack-button wire:click="sendVerification" variant="primary" class="w-full btn-primary">
            {{ __('Resend verification email') }}
        </x-artisanpack-button>

        <x-artisanpack-link class="text-sm cursor-pointer" wire:click="logout">
            {{ __('Log out') }}
        </x-artisanpack-link>
    </div>
</div>
