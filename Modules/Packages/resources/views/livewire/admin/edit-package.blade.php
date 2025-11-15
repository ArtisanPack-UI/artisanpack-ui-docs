@push('styles')
    @vite(['Modules/Packages/resources/assets/css/admin.css'])
@endpush

<section class="w-full">
    <x-artisanpack-header title="Edit Package" />

    <form wire:submit="updatePackage">
        <div class="flex flex-col md:flex-row gap-8">
            <div class="flex-2 space-y-4">
                <x-artisanpack-input wire:model.live="name" label="Name" required />

                <x-artisanpack-input wire:model.live="slug" label="Slug" required />

                <x-artisanpack-input wire:model.live="wiki_url" label="Wiki URL" type="url" required />

                <x-artisanpack-input wire:model.live="changelog_url" label="Changelog URL" type="url" required />
            </div>

            <div class="flex-1">
                <x-artisanpack-card title="Page Details">
                    <div class="space-y-4">
                        <x-artisanpack-select wire:model="homepage" label="Homepage" :options="[]" />
                    </div>

                    <x-slot:actions>
                        <x-artisanpack-button type="submit" class="btn-primary">Update Package</x-artisanpack-button>
                    </x-slot:actions>
                </x-artisanpack-card>
            </div>
        </div>
    </form>
</section>
