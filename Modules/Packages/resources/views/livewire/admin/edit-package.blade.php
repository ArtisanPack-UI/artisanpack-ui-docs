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

            <div class="flex-1 space-y-4">
                <x-artisanpack-card title="Page Details">
                    <div class="space-y-4">
                        <x-artisanpack-select wire:model="homepage" label="Homepage" :options="$pages" option-label="title" placeholder="Select a page" />

                        <x-artisanpack-input wire:model.live="icon" label="Icon" />
                    </div>

                    <x-slot:actions>
                        <x-artisanpack-button type="submit" class="btn-primary">Update Package</x-artisanpack-button>
                    </x-slot:actions>
                </x-artisanpack-card>

                <x-artisanpack-card title="Documentation">
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Import documentation from the GitLab wiki repository. This will fetch all wiki pages and create or update documentation entries for this package.
                        </p>
                    </div>

                    <x-slot:actions>
                        <x-artisanpack-button wire:click="importDocumentation" class="btn-secondary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="importDocumentation">Import Documentation</span>
                            <span wire:loading wire:target="importDocumentation">Importing...</span>
                        </x-artisanpack-button>
                    </x-slot:actions>
                </x-artisanpack-card>

                <x-artisanpack-card title="Changelog">
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Import the changelog from the GitLab repository. This will fetch the changelog file and create or update the changelog entry for this package.
                        </p>
                    </div>

                    <x-slot:actions>
                        <x-artisanpack-button wire:click="importChangelog" class="btn-secondary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="importChangelog">Import Changelog</span>
                            <span wire:loading wire:target="importChangelog">Importing...</span>
                        </x-artisanpack-button>
                    </x-slot:actions>
                </x-artisanpack-card>
            </div>
        </div>
    </form>
</section>
