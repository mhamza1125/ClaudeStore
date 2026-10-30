@props(['compact' => false, 'size' => 'h-11 sm:h-12'])
{{-- Uploaded logo (Admin → Settings) if any, otherwise the shipped Leamate logo. Width follows
     the height so the artwork is never stretched. --}}
<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center text-text']) }} aria-label="{{ $brand['name'] }} — home">
    @if ($brand['logo'])
        <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" class="logo-img {{ $size }} w-auto max-w-none">
    @else
        <img src="{{ asset(config('brand.default_logo')) }}" srcset="{{ asset(config('brand.default_logo')) }} 1x, {{ asset(str_replace('.png', '@2x.png', config('brand.default_logo'))) }} 2x"
             alt="{{ $brand['name'] }}" width="480" height="177" class="logo-img {{ $size }} w-auto max-w-none">
    @endif
</a>
