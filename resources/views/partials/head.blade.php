@props(['title' => null, 'description' => null, 'canonical' => null])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' · '.$brand['name'] : $brand['name'].' — '.$brand['tagline'] }}</title>
<meta name="description" content="{{ $description ?? $brand['tagline'] }}">
@if ($canonical)<link rel="canonical" href="{{ $canonical }}">@endif
<meta name="color-scheme" content="light dark">
{{-- Apply the theme before CSS paints to prevent a flash of the wrong theme. --}}
{{-- Mirrors resolveTheme() in resources/js/theme.js; keep the two in sync. --}}
<script>
    (function () {
        var d = document.documentElement, ds = d.dataset, valid = { white: 1, mid: 1, dark: 1, system: 1 }, stored = null;
        var norm = function (v) { v = v === 'light' ? 'mid' : v; return valid[v] ? v : null; };
        try { stored = norm(localStorage.getItem('ah-theme')); } catch (e) {}
        var fallback = norm(ds.themeDefault) || 'mid';
        var choice = ds.themeSwitch === '1' ? (norm(ds.userTheme) || stored || fallback) : fallback;
        if (ds.themeSwitch === '1' && norm(ds.userTheme)) { try { localStorage.setItem('ah-theme', choice); } catch (e) {} }
        var palette = choice === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : (ds.themeLight || 'mid')) : choice;
        ds.theme = choice;
        ds.palette = palette;
        d.classList.toggle('dark', palette === 'dark');
    })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
