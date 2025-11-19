@push('styles')
    @vite(['Modules/Admin/resources/assets/css/admin.css'])
@endpush

@push('scripts')
    @vite(['Modules/Pages/resources/assets/js/app.js'])
@endpush

<section class="w-full">
    <x-artisanpack-header title="Manage Page Menu Order" />

    <div class="mb-4">
        <a href="{{ route('dashboard.pages') }}" class="text-sm text-primary-500 hover:underline">
            ← Back to Pages
        </a>
    </div>

    <x-artisanpack-card title="Page Order">
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Drag and drop pages to reorder them. Child pages will stay with their parent page.
            </p>

            @if(count($pages) > 0)
                <div
                    x-data="{
                        handleReorder: function(event) {
                            const { orderedIds } = event.detail;
                            if (orderedIds && orderedIds.length > 0) {
                                $wire.reorderPages(orderedIds);
                            }
                        }
                    }"
                    x-drag-context
                    @drag:end="handleReorder($event)"
                    class="space-y-2"
                    role="list"
                    aria-label="Draggable page list"
                >
                    @foreach($pages as $index => $page)
                        <div
                            wire:key="page-{{ $page['id'] }}"
                            x-drag-item="{{ $page['id'] }}"
                            class="flex items-center gap-3 p-3 bg-base-200 rounded-lg cursor-move hover:bg-base-300 transition-colors"
                            role="listitem"
                        >
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                            <div class="flex-1">
                                <div class="font-medium">{{ $page['title'] }}</div>
                                <div class="text-sm text-gray-500">{{ $page['slug'] }}</div>
                            </div>
                        </div>

                        @if(isset($page['children']) && count($page['children']) > 0)
                            <div
                                x-data="{
                                    handleChildReorder: function(event) {
                                        const { orderedIds } = event.detail;
                                        if (orderedIds && orderedIds.length > 0) {
                                            $wire.reorderChildPages({{ $page['id'] }}, orderedIds);
                                        }
                                    }
                                }"
                                x-drag-context
                                @drag:end="handleChildReorder($event)"
                                class="ml-8 space-y-2"
                                role="list"
                                aria-label="Draggable child page list for {{ $page['title'] }}"
                            >
                                @foreach($page['children'] as $childIndex => $child)
                                    <div
                                        wire:key="page-child-{{ $child['id'] }}"
                                        x-drag-item="{{ $child['id'] }}"
                                        class="flex items-center gap-3 p-3 bg-base-200 rounded-lg cursor-move hover:bg-base-300 transition-colors"
                                        role="listitem"
                                    >
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                        <div class="flex-1">
                                            <div class="font-medium">{{ $child['title'] }}</div>
                                            <div class="text-sm text-gray-500">{{ $child['slug'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">No pages found.</p>
            @endif
        </div>
    </x-artisanpack-card>
</section>
