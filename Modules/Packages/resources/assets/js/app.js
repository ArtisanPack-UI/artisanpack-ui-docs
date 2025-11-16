import LivewireDragAndDrop from '@artisanpack-ui/livewire-drag-and-drop';

document.addEventListener('alpine:init', () => {
    LivewireDragAndDrop(window.Alpine);
});
