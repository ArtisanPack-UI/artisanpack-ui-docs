@push('styles')
    @vite(['Modules/Packages/resources/assets/css/admin.css'])
@endpush
<div>
    <x-artisanpack-header title="Packages">
        <x-slot:actions>
            <x-artisanpack-button icon="fas.plus" class="btn-primary" :href="route('dashboard.packages.add')" wire:navigate>Add Package</x-artisanpack-button>
        </x-slot:actions>
    </x-artisanpack-header>

    <x-artisanpack-table :headers="$headers" :rows="$this->packages">
        @scope('actions', $package)
        <div class="flex gap-2">
            <x-artisanpack-button icon="fas.edit" href="{{ route('dashboard.packages.edit', $package->id) }}" class="btn-primary btn-sm" wire:navigate>Edit</x-artisanpack-button>
            <x-artisanpack-button icon="fas.trash" class="btn-error btn-sm" wire:click="delete({{ $package->id }})" wire:confirm="Do you want to delete this package?">Delete</x-artisanpack-button>
        </div>
        @endscope
    </x-artisanpack-table>
</div>
