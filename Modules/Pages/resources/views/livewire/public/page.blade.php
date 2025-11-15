@push('styles')
    @vite(['Modules/Pages/resources/assets/css/pages.css'])
@endpush

<article>
    <x-artisanpack-header :title="$title" level="1" />

    {!! kses($content) !!}
</article>
