<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $pageTitle = isset($title) && $title ? $title . ' - ' . config('app.name') : config('app.name');
    $pageDescription = isset($metaDescription) && $metaDescription ? $metaDescription : config('app.description', 'ArtisanPack UI documentation and guides for Laravel packages.');
    $canonicalUrl = url()->current();
@endphp

<title>{{ $pageTitle }}</title>

<!-- SEO Meta Tags -->
<meta name="description" content="{{ $pageDescription }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:site_name" content="{{ config('app.name') }}">

<!-- Twitter -->
<meta name="twitter:card" content="summary">
<meta name="twitter:url" content="{{ $canonicalUrl }}">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<link rel="icon" type="image/png" href="{{ asset('images/favicon-96x96.png') }}" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}" />
<link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ asset('images/site.webmanifest') }}" />

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700,800,900" rel="stylesheet" />

<!-- Prism.js for syntax highlighting -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/toolbar/prism-toolbar.min.css" rel="stylesheet" />

@vite(['Modules/Core/resources/assets/css/app.css', 'Modules/Core/resources/assets/js/app.js'])

@stack('styles')

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
