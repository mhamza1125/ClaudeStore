{{--
    Custom 404 (real 404 status, noindex). Kept standalone on purpose: it must render even for
    URLs that matched no route (no session, no Livewire), so it does not use the store layout.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <title>Page not found | {{ $brand['name'] ?? config('brand.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    @php try { $css = \Illuminate\Support\Facades\Vite::asset('resources/css/app.css'); } catch (\Throwable) { $css = null; } @endphp
    @if ($css)<link rel="stylesheet" href="{{ $css }}">@endif
</head>
<body class="flex min-h-screen items-center justify-center bg-bg p-6 text-text">
    <main class="max-w-xl text-center">
        <p class="eyebrow">Error 404</p>
        <h1 class="h-section mt-3">This page could not be found</h1>
        <p class="mt-4 text-muted">The page may have moved or the product is no longer offered. Browse the catalogue or start from the homepage.</p>
        <p class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('catalog') }}" class="btn btn-primary">Browse the catalogue</a>
            <a href="{{ route('home') }}" class="btn btn-secondary">Homepage</a>
        </p>
    </main>
</body>
</html>
