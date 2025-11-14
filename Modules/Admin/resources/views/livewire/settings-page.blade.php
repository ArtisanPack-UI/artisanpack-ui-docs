<section class="w-full">
    <x-artisanpack-form wire:submit="save">
        <x-artisanpack-header title="Settings" />

        <x-artisanpack-input wire:model="gitLabToken" label="GitLab Token" />

        <x-slot:actions>
            <x-artisanpack-button type="submit">Save</x-artisanpack-button>
        </x-slot:actions>
    </x-artisanpack-form>
</section>
