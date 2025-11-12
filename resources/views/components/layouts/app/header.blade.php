<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <x-artisanpack-header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <x-artisanpack-sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <x-app-logo />
            </a>

            <x-artisanpack-navbar class="-mb-px max-lg:hidden">
                <x-artisanpack-navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </x-artisanpack-navbar.item>
            </x-artisanpack-navbar>

            <x-artisanpack-spacer />

            <x-artisanpack-navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <x-artisanpack-tooltip :content="__('Search')" position="bottom">
                    <x-artisanpack-navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </x-artisanpack-tooltip>
                <x-artisanpack-tooltip :content="__('Repository')" position="bottom">
                    <x-artisanpack-navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </x-artisanpack-tooltip>
                <x-artisanpack-tooltip :content="__('Documentation')" position="bottom">
                    <x-artisanpack-navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        label="Documentation"
                    />
                </x-artisanpack-tooltip>
            </x-artisanpack-navbar>

            <!-- Desktop User Menu -->
            <x-artisanpack-dropdown position="top" align="end">
                <x-artisanpack-profile
                    class="cursor-pointer"
                    :initials="auth()->user()->initials()"
                />

                <x-artisanpack-menu>
                    <x-artisanpack-menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </x-artisanpack-menu.radio.group>

                    <x-artisanpack-menu.separator />

                    <x-artisanpack-menu.radio.group>
                        <x-artisanpack-menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</x-artisanpack-menu.item>
                    </x-artisanpack-menu.radio.group>

                    <x-artisanpack-menu.separator />

                    <x-artisanpack-form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <x-artisanpack-menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </x-artisanpack-menu.item>
                    </x-artisanpack-form>
                </x-artisanpack-menu>
            </x-artisanpack-dropdown>
        </x-artisanpack-header>

        <!-- Mobile Menu -->
        <x-artisanpack-sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <x-artisanpack-sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <x-artisanpack-navlist variant="outline">
                <x-artisanpack-navlist.group :heading="__('Platform')">
                    <x-artisanpack-navlist.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                    </x-artisanpack-navlist.item>
                </x-artisanpack-navlist.group>
            </x-artisanpack-navlist>

            <x-artisanpack-spacer />

            <x-artisanpack-navlist variant="outline">
                <x-artisanpack-navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </x-artisanpack-navlist.item>

                <x-artisanpack-navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </x-artisanpack-navlist.item>
            </x-artisanpack-navlist>
        </x-artisanpack-sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
