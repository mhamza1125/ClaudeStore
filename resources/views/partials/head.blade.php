@props(['title' => null, 'description' => null, 'canonical' => null, 'ogImage' => null, 'ogType' => 'website', 'noindex' => false])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    // Homepage: "Leamate | Leather Goods & Accessories"; other pages: "<page> | Leamate".
    $pageTitle = $title ? $title.' | '.$brand['name'] : $brand['name'].' | '.$brand['tagline'];
    $metaDescription = \Illuminate\Support\Str::limit(trim((string) ($description ?: config('brand.description'))), 160);
@endphp
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
@if ($canonical)<link rel="canonical" href="{{ $canonical }}">@endif
<meta property="og:site_name" content="{{ $brand['name'] }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonical ?? url()->current() }}">
<meta property="og:image" content="{{ ($ogImage ?? null) ?: ($brand['logo'] ?? asset(str_replace('.png', '@2x.png', config('brand.default_logo')))) }}">
<meta name="twitter:card" content="{{ ($ogImage ?? null) ? 'summary_large_image' : 'summary' }}">
<meta name="theme-color" content="#faf7f2">
<meta name="color-scheme" content="light">
@if ($noindex ?? false)<meta name="robots" content="noindex, follow">@endif
{{-- Marks JS as available before CSS paints (scroll reveals only hide content when JS runs). --}}
<script>document.documentElement.classList.add('js');</script>
{{-- Self-hosted fonts (resources/fonts/web), preloaded so headings don't swap late. --}}
@foreach (['fraunces-opsz-normal', 'instrument-sans-wght-normal'] as $font)
    @php try { $fontUrl = \Illuminate\Support\Facades\Vite::asset("resources/fonts/web/{$font}.woff2"); } catch (\Throwable) { $fontUrl = null; } @endphp
    @if ($fontUrl)<link rel="preload" href="{{ $fontUrl }}" as="font" type="font/woff2" crossorigin>@endif
@endforeach
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
