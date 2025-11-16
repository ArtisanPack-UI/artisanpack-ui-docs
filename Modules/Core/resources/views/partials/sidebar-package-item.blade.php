<x-artisanpack-menu-sub
    :title="$package['name']"
    :icon="$package['icon'] ?? 'ap.puzzle'"
    :active="$package['active']"
>
    {{-- Homepage (first item) --}}
    @if(isset($package['homepage']) && $package['homepage'])
        <x-artisanpack-menu-item
            :title="$package['homepage']->title"
            icon="fas.home"
            :link="route('documentation.show', ['package' => $package['slug'], 'slug' => $package['homepage']->slug])"
            :active="$package['homepage']->active"
            route="documentation.show"
            class="flex-wrap whitespace-normal"
        />
    @endif

    {{-- Documentation hierarchy --}}
    @if(isset($package['documentation']) && count($package['documentation']) > 0)
        @foreach($package['documentation'] as $doc)
            @include('core::partials.sidebar-doc-item', ['doc' => $doc, 'packageSlug' => $package['slug']])
        @endforeach
    @endif

    {{-- Changelog (last item) --}}
    @if(isset($package['changelog']) && $package['changelog'])
        <x-artisanpack-menu-item
            title="Changelog"
            :link="route('changelog.show', ['package' => $package['slug']])"
            :active="$package['changelog']->active"
            route="changelog.show"
            class="flex-wrap whitespace-normal"
        />
    @endif
</x-artisanpack-menu-sub>
