<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <x-artisanpack-menu class="w-full">
            <x-artisanpack-menu-item title="Profile" :href="route('settings.profile')" />
            <x-artisanpack-menu-item title="Password" :href="route('settings.password')" />
            <x-artisanpack-menu-item title="Appearance" :href="route('settings.appearance')" />
        </x-artisanpack-menu>
    </div>

    <x-artisanpack-menu-separator />

    <div class="flex-1 self-stretch max-md:pt-6">
        <x-artisanpack-heading>{{ $heading ?? '' }}</x-artisanpack-heading>
        <x-artisanpack-subheading>{{ $subheading ?? '' }}</x-artisanpack-subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
