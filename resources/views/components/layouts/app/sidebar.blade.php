<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <x-artisanpack-sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <x-artisanpack-sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <x-artisanpack-navlist variant="outline">
                <x-artisanpack-navlist.group :heading="__('Platform')" class="grid">
                    <x-artisanpack-navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</x-artisanpack-navlist.item>
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

            <!-- Desktop User Menu -->
            <x-artisanpack-dropdown class="hidden lg:block" position="bottom" align="start">
                <x-artisanpack-profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <x-artisanpack-menu class="w-[220px]">
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
        </x-artisanpack-sidebar>

        <!-- Mobile User Menu -->
        <x-artisanpack-header class="lg:hidden">
            <x-artisanpack-sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <x-artisanpack-spacer />

            <x-artisanpack-dropdown position="top" align="end">
                <x-artisanpack-profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
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

        {{ $slot }}

        @fluxScripts
    </body>
</html>
