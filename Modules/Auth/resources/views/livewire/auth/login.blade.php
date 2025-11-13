<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <x-artisanpack-form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <x-artisanpack-input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Password -->
        <div class="relative">
            <x-artisanpack-input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            @if (Route::has('password.request'))
                <x-artisanpack-link class="absolute end-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </x-artisanpack-link>
            @endif
        </div>

        <!-- Remember Me -->
        <x-artisanpack-checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="flex items-center justify-end">
            <x-artisanpack-button variant="primary" type="submit" class="w-full btn-primary">{{ __('Log in') }}</x-artisanpack-button>
        </div>
    </x-artisanpack-form>

    @if (Route::has('register'))
        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <x-artisanpack-link :href="route('register')" wire:navigate>{{ __('Sign up') }}</x-artisanpack-link>
        </div>
    @endif
</div>
