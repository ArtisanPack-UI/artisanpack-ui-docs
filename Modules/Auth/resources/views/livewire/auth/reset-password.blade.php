<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <x-artisanpack-form wire:submit="resetPassword" class="flex flex-col gap-6">
        <!-- Email Address -->
        <x-artisanpack-input
            wire:model="email"
            :label="__('Email')"
            type="email"
            required
            autocomplete="email"
        />

        <!-- Password -->
        <x-artisanpack-input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
            viewable
        />

        <!-- Confirm Password -->
        <x-artisanpack-input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm password')"
            viewable
        />

        <div class="flex items-center justify-end">
            <x-artisanpack-button type="submit" variant="primary" class="w-full btn-primary">
                {{ __('Reset password') }}
            </x-artisanpack-button>
        </div>
    </x-artisanpack-form>
</div>
