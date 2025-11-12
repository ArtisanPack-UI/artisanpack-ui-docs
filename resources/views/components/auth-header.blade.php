@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <x-artisanpack-heading size="xl">{{ $title }}</x-artisanpack-heading>
    <x-artisanpack-subheading>{{ $description }}</x-artisanpack-subheading>
</div>
