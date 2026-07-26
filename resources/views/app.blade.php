<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{--
            Analytics track flag. `analytics.ts` reads this at boot and
            skips loading the tracker entirely when it is "false" so an
            admin poking at the live site while logged in does not skew
            visitor stats. This meta tag is the whole gate — the
            /api/analytics/* routes ride the `api` middleware group
            which has no `StartSession`, so no server-side auth check
            can distinguish authed vs guest beacons; the client-side
            skip is the honest layer.
        --}}
        <meta name="analytics-track" content="{{ auth()->check() ? 'false' : 'true' }}">

        <title inertia>{{ config('app.name', 'ArtisanPack UI Docs') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preload" as="font" type="font/woff2" href="/fonts/poppins/poppins-400-latin.woff2" crossorigin>
        <link rel="preload" as="font" type="font/woff2" href="/fonts/poppins/poppins-500-latin.woff2" crossorigin>

        @include('partials.theme-boot')

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
        @speculativeRules
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
