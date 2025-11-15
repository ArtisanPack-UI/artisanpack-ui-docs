<section class="w-full">
    <x-artisanpack-form wire:submit="save">
        <x-artisanpack-header title="Settings" />

        <x-artisanpack-input wire:model="gitLabToken" label="GitLab Token" />

        <x-artisanpack-select wire:model="homePage" :options="$this->pages" label="Home Page" option-label="title" placeholder="Select a page" />

        <x-slot:actions>
            <x-artisanpack-button type="submit">Save</x-artisanpack-button>
        </x-slot:actions>
    </x-artisanpack-form>
</section>
