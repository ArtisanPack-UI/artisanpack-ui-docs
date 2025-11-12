<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';
	public bool $deleteUserModal = false;

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <x-artisanpack-heading>{{ __('Delete account') }}</x-artisanpack-heading>
        <x-artisanpack-subheading>{{ __('Delete your account and all of its resources') }}</x-artisanpack-subheading>
    </div>

    <x-artisanpack-button variant="danger" x-data="" @click="$wire.deleteUserModal = true" class="btn-danger">
        {{ __('Delete account') }}
    </x-artisanpack-button>

    <x-artisanpack-modal wire:model="deleteUserModal" :show="$errors->isNotEmpty()" focusable>
        <x-artisanpack-form wire:submit="deleteUser" class="space-y-6">
            <div>
                <x-artisanpack-heading size="lg">{{ __('Are you sure you want to delete your account?') }}</x-artisanpack-heading>

                <x-artisanpack-subheading>
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </x-artisanpack-subheading>
            </div>

            <x-artisanpack-input wire:model="password" :label="__('Password')" type="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <x-artisanpack-button variant="filled" @click="$wire.deleteUserModal = false">{{ __('Cancel') }}</x-artisanpack-button>

                <x-artisanpack-button variant="danger" type="submit">{{ __('Delete account') }}</x-artisanpack-button>
            </div>
        </x-artisanpack-form>
    </x-artisanpack-modal>
</section>
