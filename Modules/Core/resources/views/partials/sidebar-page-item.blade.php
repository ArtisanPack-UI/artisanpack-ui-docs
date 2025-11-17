@php use Modules\Core\Setting; @endphp
@if(isset($page['children']) && count($page['children']) > 0)
    {{-- Page with children - render as submenu --}}
    <x-artisanpack-menu-sub
        :title="$page['title']"
        :icon="isset($isChild) && $isChild ? null : ($page['icon'] ?? 'fas.file')"
        :active="$page['active']"
        :open="$page['active']"
    >
        @php
            // Build link for parent page
            $parentRoute = null;
            $parentLink = null;
            $homepage = Setting::where('key', 'homePage')->first()->value ?? 0;

            if ($page['parent']) {
                $parentOfParent = \Modules\Pages\Page::find($page['parent']);
                if ($parentOfParent) {
                    $parentRoute = 'page.child';
                    $parentLink = route('page.child', ['parentSlug' => $parentOfParent->slug, 'slug' => $page['slug']]);
                }
            } else {
                $parentRoute = 'page.show';
                $parentLink = route('page.show', ['slug' => $page['slug']]);
            }
        @endphp

        <!-- DEBUG: {{ $page['title'] }} (parent in submenu) - active: {{ json_encode($page['active'] ?? 'N/A') }}, isCurrentPage: {{ json_encode($page['isCurrentPage'] ?? 'N/A') }} -->
        <x-artisanpack-menu-item
            :title="$page['title']"
            :link="$parentLink"
            :active="$page['isCurrentPage'] ?? false"
            exact
            class="whitespace-normal"
        />

        @foreach($page['children'] as $child)
            @include('core::partials.sidebar-page-item', ['page' => $child, 'isChild' => true])
        @endforeach
    </x-artisanpack-menu-sub>
@else
    {{-- Single page item --}}
    @php
        $route = null;
        $link = null;
        $homepage = Setting::where('key', 'homePage')->first()->value ?? 0;

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

        // Check if this is the homepage AND we're actually on the homepage URL
        if ($page['id'] === intval($homepage) && request()->segment(1) === null) {
            $page['isCurrentPage'] = true;
        }
    @endphp

    <!-- DEBUG: {{ $page['title'] }} (single) - active: {{ json_encode($page['active'] ?? 'N/A') }}, isCurrentPage: {{ json_encode($page['isCurrentPage'] ?? 'N/A') }} -->
    <x-artisanpack-menu-item
        :title="$page['title']"
        :icon="isset($isChild) && $isChild ? null : ($page['icon'] ?? 'fas.file')"
        :link="$link"
        :active="$page['isCurrentPage'] ?? false"
        exact
        class="whitespace-normal"
    />
@endif
