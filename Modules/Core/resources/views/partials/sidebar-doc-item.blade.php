@if(isset($doc['children']) && count($doc['children']) > 0)
    {{-- Documentation with children - render as submenu --}}
    <x-artisanpack-menu-sub
        :title="$doc['title']"
        :active="$doc['active']"
        :open="$doc['active']"
    >
        <!-- DEBUG: {{ $doc['title'] }} - active: {{ json_encode($doc['active'] ?? 'N/A') }}, isCurrentPage: {{ json_encode($doc['isCurrentPage'] ?? 'N/A') }} -->
        <x-artisanpack-menu-item
            :title="$doc['title']"
            :link="route('documentation.show', ['package' => $packageSlug, 'slug' => $doc['slug']])"
            :active="$doc['isCurrentPage'] ?? false"
            route="documentation.show"
            class="flex-wrap max-w-[100%] whitespace-normal block"
        />

        @foreach($doc['children'] as $child)
            @include('core::partials.sidebar-doc-item', ['doc' => $child, 'packageSlug' => $packageSlug])
        @endforeach
    </x-artisanpack-menu-sub>
@else
    {{-- Single documentation item --}}
    <!-- DEBUG: {{ $doc['title'] }} - active: {{ json_encode($doc['active'] ?? 'N/A') }}, isCurrentPage: {{ json_encode($doc['isCurrentPage'] ?? 'N/A') }} -->
    <x-artisanpack-menu-item
        :title="$doc['title']"
        :link="route('documentation.show', ['package' => $packageSlug, 'slug' => $doc['slug']])"
        :active="$doc['isCurrentPage'] ?? false"
        route="documentation.show"
        class="flex-wrap max-w-[100%] whitespace-normal block"
    />
@endif
