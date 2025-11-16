@push('styles')
    @vite(['Modules/Pages/resources/assets/css/pages.css'])
@endpush

@php
    // Share tableOfContents with the layout
    View::share('tableOfContents', $tableOfContents);
@endphp

<article>
    <x-artisanpack-header :title="$title" level="1" />

    {!! $content !!}
</article>
