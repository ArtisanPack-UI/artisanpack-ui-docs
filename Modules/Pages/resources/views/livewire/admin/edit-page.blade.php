@push('styles')
    @vite(['Modules/Pages/resources/assets/css/admin.css'])
@endpush

<section class="w-full">
    <x-artisanpack-header title="Edit Page">
        <x-slot:actions>
            <x-artisanpack-button :href="route('dashboard.pages.add')" class="btn-primary" icon="fas.plus" wire:navigate>Add Page</x-artisanpack-button>
        </x-slot:actions>
    </x-artisanpack-header>

    <form wire:submit="save">
        <div class="flex flex-col md:flex-row gap-8">
            <div class="flex-2 space-y-4">
                <x-artisanpack-input wire:model.live="title" label="Title" required />

                <x-artisanpack-input wire:model.live="slug" label="Slug" required />

                <x-artisanpack-editor wire:model="content" />
            </div>

            <div class="flex-1">
                <x-artisanpack-card title="Page Details">
                    <div class="space-y-4">
                        <x-artisanpack-select wire:model="parent_id" label="Parent Page" :options="$this->pages" />

                        <x-artisanpack-input type="number" wire:model.live="menu_order" label="Order" />

                        <x-artisanpack-input wire:model.live="icon" label="Icon" />
                    </div>

                    <x-slot:actions>
                        <x-artisanpack-button type="submit" class="btn-primary">Update Page</x-artisanpack-button>
                    </x-slot:actions>
                </x-artisanpack-card>
            </div>
        </div>
    </form>
</section>
