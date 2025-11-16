@if(isset($doc['children']) && count($doc['children']) > 0)
    {{-- Documentation with children - render as submenu --}}
    <x-artisanpack-menu-sub
        :title="$doc['title']"
        :active="$doc['active']"
    >
        @foreach($doc['children'] as $child)
            @include('core::partials.sidebar-doc-item', ['doc' => $child, 'packageSlug' => $packageSlug])
        @endforeach
    </x-artisanpack-menu-sub>
@else
    {{-- Single documentation item --}}
    <x-artisanpack-menu-item
        :title="$doc['title']"
        :link="route('documentation.show', ['package' => $packageSlug, 'slug' => $doc['slug']])"
        :active="$doc['active']"
        route="documentation.show"
        class="flex-wrap max-w-[100%] whitespace-normal block"
    />
@endif
