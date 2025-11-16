@push('styles')
    @vite(['Modules/Packages/resources/assets/css/admin.css'])
@endpush

@push('scripts')
    @vite(['Modules/Packages/resources/assets/js/app.js'])
@endpush

<section class="w-full">
    <x-artisanpack-header title="Manage Documentation - {{ $package->name }}" />

    <div class="mb-4">
        <a href="{{ route('dashboard.packages.edit', $package) }}" class="text-sm text-primary-500 hover:underline">
            ← Back to Package
        </a>
    </div>

    <x-artisanpack-card title="Documentation Order">
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Drag and drop documentation pages to reorder them. Child pages will stay with their parent page.
            </p>

            @if(count($documentation) > 0)
                <div
                    x-data="{
                        handleReorder: function(event) {
                            const { oldIndex, newIndex } = event.detail;
                            if (oldIndex !== newIndex) {
                                $wire.reorderDocumentation(oldIndex, newIndex);
                            }
                        }
                    }"
                    x-drag-context="handleReorder"
                    @drag:end="handleReorder($event)"
                    class="space-y-2"
                    role="list"
                    aria-label="Draggable documentation list"
                >
                    @foreach($documentation as $index => $doc)
                        <div
                            wire:key="doc-{{ $doc['id'] }}"
                            x-drag-item="{{ json_encode(['id' => $doc['id'], 'index' => $index, 'title' => $doc['title']]) }}"
                            class="flex items-center gap-3 p-3 bg-base-200 rounded-lg cursor-move hover:bg-base-300 transition-colors"
                            role="listitem"
                        >
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                            <div class="flex-1">
                                <div class="font-medium">{{ $doc['title'] }}</div>
                                <div class="text-sm text-gray-500">{{ $doc['slug'] }}</div>
                            </div>
                        </div>

                        @if(isset($doc['children']) && count($doc['children']) > 0)
                            <div class="ml-8 space-y-2">
                                @foreach($doc['children'] as $childIndex => $child)
                                    <div
                                        wire:key="doc-child-{{ $child['id'] }}"
                                        class="flex items-center gap-3 p-3 bg-base-200 rounded-lg opacity-75"
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
                <p class="text-gray-500 dark:text-gray-400">No documentation pages found for this package.</p>
            @endif
        </div>
    </x-artisanpack-card>
</section>
