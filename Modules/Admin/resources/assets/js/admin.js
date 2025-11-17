import "@artisanpack-ui/livewire-drag-and-drop";

// Re-apply theme on Livewire navigation to prevent theme flickering
function applyTheme() {
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.classList.remove('dark');
    }
}

// Listen for Livewire navigation events
document.addEventListener('livewire:navigated', () => {
    applyTheme();
});