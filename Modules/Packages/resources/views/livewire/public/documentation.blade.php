@push('styles')
    @vite(['Modules/Packages/resources/assets/css/packages.css'])
@endpush

@php
    // Share tableOfContents with the layout
    View::share('tableOfContents', $tableOfContents);
@endphp

<article>
    <p>{{ $packageName }} - v{{ $version }}</p>

    <x-artisanpack-header :title="$title" level="1" />

    {!! $content !!}
</article>
