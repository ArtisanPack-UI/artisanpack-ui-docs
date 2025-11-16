@if(isset($page['children']) && count($page['children']) > 0)
    {{-- Page with children - render as submenu --}}
    <x-artisanpack-menu-sub
        :title="$page['title']"
        :icon="isset($isChild) && $isChild ? null : ($page['icon'] ?? 'fas.file')"
        :active="$page['active']"
    >
        @foreach($page['children'] as $child)
            @include('core::partials.sidebar-page-item', ['page' => $child, 'isChild' => true])
        @endforeach
    </x-artisanpack-menu-sub>
@else
    {{-- Single page item --}}
    @php
        $route = null;
        $link = null;

        if ($page['parent']) {
            // Child page - need to find parent slug
            $parentPage = \Modules\Pages\Page::find($page['parent']);
            if ($parentPage) {
                $route = 'page.child';
                $link = route('page.child', ['parentSlug' => $parentPage->slug, 'slug' => $page['slug']]);
            }
        } else {
            // Top-level page
            $route = 'page.show';
            $link = route('page.show', ['slug' => $page['slug']]);
        }
    @endphp

    <x-artisanpack-menu-item
        :title="$page['title']"
        :icon="isset($isChild) && $isChild ? null : ($page['icon'] ?? 'fas.file')"
        :link="$link"
        :active="$page['active']"
        exact
        class="whitespace-normal"
    />
@endif
