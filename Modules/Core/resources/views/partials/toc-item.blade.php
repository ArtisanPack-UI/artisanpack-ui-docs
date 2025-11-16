<div class="toc-item">
    <a
        href="#{{ $heading['id'] }}"
        class="toc-link block py-1 px-2 text-sm rounded transition-colors hover:bg-base-200 {{ $heading['level'] > 2 ? 'ml-' . (($heading['level'] - 2) * 4) : '' }}"
        data-target="{{ $heading['id'] }}"
    >
        {{ $heading['text'] }}
    </a>

    @if(isset($heading['children']) && count($heading['children']) > 0)
        <div class="ml-4">
            @foreach($heading['children'] as $child)
                @include('core::partials.toc-item', ['heading' => $child])
            @endforeach
        </div>
    @endif
</div>
