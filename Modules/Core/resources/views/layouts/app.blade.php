<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('core::partials.head')
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">
<div class="bg-background">
    @include('core::partials.header')

    <div id="site-content" class="w-full max-w-[90rem] mx-auto py-20 px-4 md:px-0">
        <!-- Mobile: Main Sidebar Dropdown (visible on mobile only) -->
        <div class="md:hidden mb-4">
            <details class="dropdown w-full">
                <summary class="btn btn-outline w-full justify-between">
                    <span>Menu</span>
                    <x-artisanpack-icon name="fas.bars" />
                </summary>
                <div class="dropdown-content z-[1] mt-2 w-full bg-base-100 rounded-lg shadow-lg max-h-[70vh] overflow-y-auto">
                    @include('core::partials.main-sidebar')
                </div>
            </details>
        </div>

        <!-- Grid Layout for Desktop, Stack for Mobile -->
        <div class="grid grid-cols-1 md:grid-cols-[20rem_1fr] lg:grid-cols-[20rem_1fr_20rem] gap-8">
            <!-- Desktop: Main Sidebar (hidden on mobile) -->
            <div class="hidden md:block md:self-start md:sticky md:top-20">
                @include('core::partials.main-sidebar')
            </div>

            <!-- Main Content Area -->
            <main id="main" class="w-full min-w-0">
                <!-- Mobile: TOC Dropdown (visible on mobile only, before content) -->
                <div class="md:hidden mb-4">
                    @if(isset($tableOfContents) && count($tableOfContents) > 0)
                        <details class="collapse collapse-arrow bg-base-100 border border-base-300">
                            <summary class="collapse-title text-lg font-medium">
                                Table of Contents
                            </summary>
                            <div class="collapse-content">
                                <nav id="toc-nav-mobile" class="space-y-1">
                                    @foreach($tableOfContents as $heading)
                                        @include('core::partials.toc-item', ['heading' => $heading])
                                    @endforeach
                                </nav>
                            </div>
                        </details>
                    @endif
                </div>

                {{ $slot }}
            </main>

            <!-- Desktop: Secondary Sidebar (hidden on mobile and tablet) -->
            <div class="hidden lg:block lg:self-start lg:sticky lg:top-20">
                @include('core::partials.secondary-sidebar')
            </div>
        </div>
    </div>

    @include('core::partials.footer')
</div>
<x-artisanpack-spotlight />

<!-- Prism.js Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/toolbar/prism-toolbar.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/normalize-whitespace/prism-normalize-whitespace.min.js"></script>

@stack('scripts')
@livewireScripts
</body>
</html>
