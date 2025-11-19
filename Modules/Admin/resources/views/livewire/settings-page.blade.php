<section class="w-full">
    <x-artisanpack-form wire:submit="save">
        <x-artisanpack-header title="Settings" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-artisanpack-card title="General">
                <div class="space-y-4">
                    <x-artisanpack-select wire:model="homePage" :options="$this->pages" label="Home Page" option-label="title" placeholder="Select a page" />
                </div>
            </x-artisanpack-card>

            <x-artisanpack-card title="Integrations">
                <div class="space-y-4">
                    <x-artisanpack-input wire:model="gitLabToken" label="GitLab Token" type="password" />

                    <x-artisanpack-input wire:model="googleAnalyticsId" label="Google Analytics ID" placeholder="G-XXXXXXXXXX" hint="Your GA4 Measurement ID (e.g., G-ABC123XYZ)" />
                </div>
            </x-artisanpack-card>
        </div>

        <x-slot:actions>
            <x-artisanpack-button type="submit">Save</x-artisanpack-button>
        </x-slot:actions>
    </x-artisanpack-form>
</section>
