@push('styles')
    @vite(['Modules/Packages/resources/assets/css/packages.css'])
@endpush

<article>
    <x-artisanpack-header :title="$title" level="1" />

    {!! Str::markdown($content) !!}
</article>
