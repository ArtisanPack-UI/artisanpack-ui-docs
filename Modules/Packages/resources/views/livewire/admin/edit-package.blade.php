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

                        <x-artisanpack-input wire:model="icon" label="Icon" />

                        <x-artisanpack-input wire:model="version" label="Version" />

                        <x-artisanpack-select
                            wire:model="package_registry"
                            label="Package Registry"
                            placeholder="Select registry type"
                            :options="[
                                ['id' => 'packagist', 'name' => 'Packagist (Composer)'],
                                ['id' => 'npm', 'name' => 'NPM (JavaScript)'],
                            ]"
                            option-value="id"
                            option-label="name"
                            hint="Package name will be auto-generated from slug"
                        />
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
                        <a href="{{ route('dashboard.packages.documentation', $package) }}" class="btn btn-secondary">
                            Manage Order
                        </a>
                        <x-artisanpack-button wire:click="importDocumentation" class="btn-secondary" spinner="1" loading="Importing..." label="Import Documentation" />
                    </x-slot:actions>
                </x-artisanpack-card>

                <x-artisanpack-card title="Changelog">
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Import the changelog from the GitLab repository. This will fetch the changelog file and create or update the changelog entry for this package.
                        </p>
                    </div>

                    <x-slot:actions>
                        <x-artisanpack-button wire:click="importChangelog" class="btn-secondary" spinner="1" loading="Importing..." label="Import Changelog" />
                    </x-slot:actions>
                </x-artisanpack-card>
            </div>
        </div>
    </form>
</section>
