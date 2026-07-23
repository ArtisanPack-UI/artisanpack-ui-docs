{{-- Runs synchronously in <head> to resolve the color scheme before first paint (no FOUC). --}}
@php
    $__userThemePreference = auth()->check() ? auth()->user()->theme_preference : null;
    $__userThemePreference = in_array($__userThemePreference, ['light', 'dark', 'system'], true)
        ? $__userThemePreference
        : null;
@endphp
<script>
    (function () {
        var server = @json($__userThemePreference);
        var stored;
        try { stored = localStorage.getItem('theme'); } catch (e) { stored = null; }
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var pref = server || stored;
        var theme = pref === 'dark' || pref === 'light'
            ? pref
            : (prefersDark ? 'dark' : 'light');
        var root = document.documentElement;
        root.setAttribute('data-theme', theme);
        root.classList.toggle('dark', theme === 'dark');
    })();
</script>
