<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@env('testing')
    <!-- Vite assets are skipped in testing to avoid manifest/hot file lookups. -->
@else
    @php
        $adminBuildDir = 'build-admin';
        $adminHotFile = public_path('hot-admin');
        $adminManifest = public_path($adminBuildDir . '/manifest.json');
    @endphp

    @if (file_exists($adminManifest) || file_exists($adminHotFile))
        {!! vite([
            'Modules/Admin/resources/assets/css/admin.css',
            'Modules/Admin/resources/assets/js/admin.js',
        ])->useBuildDirectory('build-admin')->useHotFile(public_path('hot-admin')) !!}
    @else
        {{-- Fallback to root app assets if the Admin manifest/hot file is not present to avoid 500s. --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
@endenv

<script>
    // On page load or when changing themes, best to add inline in `head` to avoid FOUC
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark')
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.classList.remove('dark')
    }
</script>
