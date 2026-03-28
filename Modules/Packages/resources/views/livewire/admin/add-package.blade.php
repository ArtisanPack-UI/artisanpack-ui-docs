@push('styles')
    @vite(['Modules/Packages/resources/assets/css/admin.css'])
@endpush

<section class="w-full">
    <x-artisanpack-header title="Add Package" />

    <form wire:submit="addPackage">
        <div class="flex flex-col md:flex-row gap-8">
            <div class="flex-2 space-y-4">
                <x-artisanpack-input wire:model.live="name" label="Name" required />

                <x-artisanpack-input wire:model.live="slug" label="Slug" required />

                <x-artisanpack-input wire:model.live="wiki_url" label="Wiki URL" type="url" required hint="GitHub wiki URL (e.g. https://github.com/owner/repo/wiki) or GitLab wiki URL" />

                <x-artisanpack-input wire:model.live="changelog_url" label="Changelog URL" type="url" required hint="GitHub file URL (e.g. https://github.com/owner/repo/blob/main/CHANGELOG.md), raw GitHub URL (raw.githubusercontent.com), or GitLab file URL" />
            </div>

            <div class="flex-1">
                <x-artisanpack-card title="Page Details">
                    <div class="space-y-4">
                        <x-artisanpack-select wire:model="home" label="Homepage" :options="[]" />

                        <x-artisanpack-input wire:model.live="icon" label="Icon" />

                        <x-artisanpack-input wire:model.live="version" label="Version" />

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
                        <x-artisanpack-button type="submit" class="btn-primary">Publish Package</x-artisanpack-button>
                    </x-slot:actions>
                </x-artisanpack-card>
            </div>
        </div>
    </form>
</section>
