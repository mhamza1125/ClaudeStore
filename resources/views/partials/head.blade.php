@props(['title' => null, 'description' => null, 'canonical' => null])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' · '.$brand['name'] : $brand['name'].' — '.$brand['tagline'] }}</title>
<meta name="description" content="{{ $description ?? $brand['tagline'] }}">
@if ($canonical)<link rel="canonical" href="{{ $canonical }}">@endif
<meta name="color-scheme" content="light dark">
{{-- Apply the theme before CSS paints to prevent a flash of the wrong theme. --}}
<script>
    (function () {
        var d = document.documentElement, stored = null;
        try { stored = localStorage.getItem('ah-theme'); } catch (e) {}
        var choice = d.dataset.userTheme || stored || 'system';
        if (d.dataset.userTheme) { try { localStorage.setItem('ah-theme', choice); } catch (e) {} }
        var dark = choice === 'dark' || (choice === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        d.classList.toggle('dark', dark);
        d.dataset.theme = choice;
    })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
