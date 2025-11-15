<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('admin::partials.head')
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

{{-- NAVBAR mobile only --}}
<x-artisanpack-nav sticky class="lg:hidden">
    <x-slot:brand>
        <x-app-logo />
    </x-slot:brand>
    <x-slot:actions>
        <label for="main-drawer" class="lg:hidden me-3">
            <x-artisanpack-icon name="o-bars-3" class="cursor-pointer" />
        </label>
    </x-slot:actions>
</x-artisanpack-nav>

{{-- MAIN --}}
<x-artisanpack-main :full-width="true">
    {{-- SIDEBAR --}}
    <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

        {{-- BRAND --}}
        <x-app-logo class="px-5 pt-4" />

        {{-- MENU --}}
        <x-artisanpack-menu :title="null" activate-by-route class="flex flex-col flex-1">
            <x-artisanpack-menu-item title="Dashboard" icon="fas.gauge-high" :href="route('dashboard')" exact route="dashboard" />
            <x-artisanpack-menu-sub title="Pages" icon="fas.file">
                <x-artisanpack-menu-item title="Pages" :href="route('dashboard.pages')" exact route="dashboard.pages" />
                <x-artisanpack-menu-item title="Add Page" :href="route('dashboard.pages.add')" exact route="dashboard.pages.add" />
            </x-artisanpack-menu-sub>
            <x-artisanpack-menu-sub title="Packages" icon="fas.puzzle-piece">
                <x-artisanpack-menu-item title="Packages" :href="route('dashboard.packages')" exact route="dashboard.packages" />
                <x-artisanpack-menu-item title="Add Package" :href="route('dashboard.packages.add')" exact route="dashboard.packages.add" />
            </x-artisanpack-menu-sub>
            <x-artisanpack-menu-item title="Settings" icon="fas.cogs" :href="route('settings')" exact route="settings" />

            <x-artisanpack-menu-separator />

            <div class="mt-auto">
            {{-- User --}}
            @if($user = auth()->user())

                <x-artisanpack-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="-mx-2 !-my-2 rounded mt-auto">
                    <x-slot:actions>
                        <x-artisanpack-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="logoff" no-wire-navigate link="/logout" />
                    </x-slot:actions>
                </x-artisanpack-list-item>

                <x-artisanpack-menu-separator />
            @endif
            </div>

        </x-artisanpack-menu>
    </x-slot:sidebar>

    {{-- The `$slot` goes here --}}
    <x-slot:content>
        {{ $slot }}
    </x-slot:content>
</x-artisanpack-main>

{{--  TOAST area --}}
<x-artisanpack-toast />
<x-artisanpack-spotlight />
@stack('styles')
@livewireScripts
</body>
</html>
