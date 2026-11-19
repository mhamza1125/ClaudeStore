@props(['title' => null, 'description' => null, 'canonical' => null, 'ogImage' => null, 'ogType' => 'website', 'noindex' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title, 'description' => $description, 'canonical' => $canonical, 'ogImage' => $ogImage, 'ogType' => $ogType, 'noindex' => $noindex])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
<a href="#main" class="btn btn-primary sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50">Skip to content</a>
@include('partials.store-header')
<main id="main" class="flex-1">
    {{ $slot }}
</main>
@include('partials.store-footer')
<x-whatsapp-button/>
<x-chat-widget/>
@livewireScriptConfig
@stack('scripts')
</body>
</html>
