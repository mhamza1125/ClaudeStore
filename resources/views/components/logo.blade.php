@props(['compact' => false, 'size' => 'h-11 sm:h-12', 'tone' => 'dark'])
{{-- Uploaded logo (Admin → Settings) if any, otherwise the shipped Leamate logo. tone="light" is for
     dark backgrounds: the shipped logo has a recoloured cream/tan version; an uploaded logo gets a
     light backing so its own colours stay legible. Width follows the height (never stretched). --}}
@php
    $file = config('brand.default_logo');
    if ($tone === 'light') { $file = str_replace('.png', '-light.png', $file); }
@endphp
<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center text-text']) }} aria-label="{{ $brand['name'] }} — home">
    @if ($brand['logo'])
        <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" class="{{ $size }} w-auto max-w-none {{ $tone === 'light' ? 'rounded-md bg-on-band px-2 py-1' : '' }}">
    @else
        <img src="{{ asset($file) }}" srcset="{{ asset($file) }} 1x, {{ asset(str_replace('.png', '@2x.png', $file)) }} 2x"
             alt="{{ $brand['name'] }}" width="480" height="177" class="{{ $size }} w-auto max-w-none">
    @endif
</a>
