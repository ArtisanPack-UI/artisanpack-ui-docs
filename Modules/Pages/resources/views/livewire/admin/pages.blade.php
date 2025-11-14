@push('styles')
    @vite(['Modules/Pages/Resources/assets/css/admin.css'])
@endpush
<div>
    <x-artisanpack-header title="Pages">
        <x-slot:actions>
            <x-artisanpack-button icon="fas.plus" href="{{ route('dashboard.pages.add') }}" class="btn-primary" wire:navigate>Add Page</x-artisanpack-button>
        </x-slot:actions>
    </x-artisanpack-header>

    <x-artisanpack-table :headers="$headers" :rows="$this->pages">
        @scope('actions', $page)
        <div class="flex gap-2">
            <x-artisanpack-button icon="fas.edit" href="{{ route('dashboard.pages.edit', $page->id) }}" class="btn-primary btn-sm" wire:navigate>Edit</x-artisanpack-button>
            <x-artisanpack-button icon="fas.trash" class="btn-error btn-sm" wire:confirm="delete({{ $page->id }})">Delete</x-artisanpack-button>
        </div>
        @endscope
    </x-artisanpack-table>
</div>