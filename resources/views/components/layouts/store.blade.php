@props(['title' => null, 'description' => null, 'canonical' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      @auth data-user-theme="{{ auth()->user()->theme }}" data-theme-url="{{ route('preferences.theme') }}" @endauth>
<head>
    @include('partials.head', ['title' => $title, 'description' => $description, 'canonical' => $canonical])
    @stack('head')
</head>
<body class="min-h-screen flex flex-col">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 btn btn-primary">Skip to content</a>
@include('partials.store-header')
<main id="main" class="flex-1">
    {{ $slot }}
</main>
@include('partials.store-footer')
@livewireScriptConfig
@stack('scripts')
</body>
</html>
