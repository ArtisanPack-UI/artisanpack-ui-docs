{{-- Runs synchronously in <head> to resolve the color scheme before first paint (no FOUC). --}}
<script>
    (function () {
        var stored;
        try { stored = localStorage.getItem('theme'); } catch (e) { stored = null; }
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var theme = stored === 'dark' || stored === 'light' ? stored : (prefersDark ? 'dark' : 'light');
        var root = document.documentElement;
        root.setAttribute('data-theme', theme);
        root.classList.toggle('dark', theme === 'dark');
    })();
</script>
