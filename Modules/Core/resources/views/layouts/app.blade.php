<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('core::partials.head')
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">
<div class="bg-background">
    @include('core::partials.header')

    <div id="site-content" class="w-full max-w-[90rem] mx-auto flex flex-wrap py-20 gap-8">
        @include('core::partials.main-sidebar')

        <main id="main" class="w-full flex-1 h-full">
            {{ $slot }}
        </main>

        @include('core::partials.secondary-sidebar')
    </div>

    @include('core::partials.footer')
</div>
<x-artisanpack-spotlight />
@livewireScripts
</body>
</html>
