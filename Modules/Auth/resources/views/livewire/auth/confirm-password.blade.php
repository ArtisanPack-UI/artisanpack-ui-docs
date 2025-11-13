<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Confirm password')"
        :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <x-artisanpack-form wire:submit="confirmPassword" class="flex flex-col gap-6">
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

        <x-artisanpack-button variant="primary" type="submit" class="w-full btn-primary">{{ __('Confirm') }}</x-artisanpack-button>
    </x-artisanpack-form>
</div>
