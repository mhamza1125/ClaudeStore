{{-- The one product card, used on the homepage, catalogue, search and related products.
     Image-led: a 4:5 photograph, at most one badge, then category, name, SKU · MOQ, price and
     a quote action. "compact" is kept for callers (carousels) and only makes the card fill its cell. --}}
@props(['product', 'compact' => false])
@php
    $image = $product->primaryImage();
    $price = app(\App\Services\CatalogPricing::class)->summary($product, app(\App\Pricing\PriceAccess::class)->current());
    $badge = $product->is_raw_leather ? 'Raw leather' : ($product->is_custom ? 'Custom' : ($product->is_demo ? 'Demo product' : null));
@endphp
<article {{ $attributes->merge(['class' => 'group flex flex-col'.($compact ? ' h-full' : '')]) }}>
    <a href="{{ route('products.show', $product) }}" class="relative block overflow-hidden rounded-sm bg-elevated" @if ($compact) tabindex="-1" aria-hidden="true" @endif>
        @if ($image)
            <img src="{{ $image->url('card') }}" alt="{{ $image->alt ?? $product->name }}" loading="lazy" width="800" height="1000" draggable="false"
                 class="protected-img aspect-[4/5] w-full object-cover transition duration-700 group-hover:scale-[1.04] motion-reduce:transition-none">
            <x-image-watermark size="md"/>
        @else
            <div class="flex aspect-[4/5] items-center justify-center text-muted"><x-icon name="image" class="h-10 w-10"/><span class="sr-only">Image coming soon</span></div>
        @endif
        @if ($badge)
            <span class="absolute left-3 top-3 z-[2]"><x-badge tone="{{ $product->is_demo && ! $product->is_raw_leather && ! $product->is_custom ? 'neutral' : 'accent' }}">{{ $badge }}</x-badge></span>
        @endif
    </a>
    <div class="mt-4 flex flex-1 flex-col">
        <p class="text-[0.6875rem] font-medium uppercase tracking-[0.14em] text-muted">{{ $product->category?->name }}</p>
        <h3 class="mt-1 line-clamp-2 min-h-[2.4em] font-serif text-xl leading-tight sm:text-2xl"><a href="{{ route('products.show', $product) }}" class="transition-colors hover:text-accent">{{ $product->name }}</a></h3>
        <p class="mt-1 text-xs text-muted">SKU {{ $product->sku }} · MOQ {{ $product->moq }}</p>
        <div class="mt-3 flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1">
            <p class="text-sm">
                @if ($price['cents'] !== null)
                    <span class="text-muted">{{ $price['label'] }}</span> <span class="font-semibold">{{ money($price['cents']) }}</span>
                @else
                    <span class="font-medium">{{ $price['label'] }}</span>
                @endif
            </p>
            <x-badge :tone="$product->availability->tone()">{{ $product->availability->label() }}</x-badge>
        </div>
        @if ($price['note'])<p class="mt-1 text-xs text-accent">{{ $price['note'] }}</p>@endif
        <a href="{{ route('products.show', $product) }}" class="mt-auto inline-flex items-center gap-1.5 pt-4 text-xs font-semibold uppercase tracking-[0.14em] text-text transition-colors hover:text-accent" aria-label="View and quote {{ $product->name }}">View &amp; quote <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"/></a>
    </div>
</article>
