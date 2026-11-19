{{-- The one product card, used on the homepage, catalogue, search and related products.
     Image-led: a 4:5 photograph in a rounded frame, at most one badge, then category, name,
     SKU · MOQ, price and availability. The whole card is one link target (the name's link is
     stretched); a container query hides the SKU/MOQ line when the card is narrow.
     "compact" is kept for callers (carousels) and only makes the card fill its cell. --}}
@props(['product', 'compact' => false])
@php
    $image = $product->primaryImage();
    $price = app(\App\Services\CatalogPricing::class)->summary($product, app(\App\Pricing\PriceAccess::class)->current());
    $badge = $product->is_raw_leather ? 'Raw leather' : ($product->is_custom ? 'Custom' : ($product->is_demo ? 'Demo product' : null));
    $tone = $product->availability->tone();
    $dot = ['success' => 'bg-success', 'warning' => 'bg-warning', 'danger' => 'bg-danger', 'accent' => 'bg-accent'][$tone] ?? 'bg-muted';
@endphp
<article {{ $attributes->merge(['class' => 'product-card group relative flex flex-col'.($compact ? ' h-full' : '')]) }}>
    <div class="product-media">
        @if ($image)
            <img src="{{ $image->url('card') }}" alt="{{ $image->alt ?? $product->name }}" loading="lazy" width="800" height="1000" draggable="false"
                 class="protected-img aspect-[4/5] w-full object-cover motion-reduce:transition-none">
            <x-image-watermark size="md"/>
        @else
            <div class="flex aspect-[4/5] items-center justify-center text-muted"><x-icon name="image" class="h-10 w-10"/><span class="sr-only">Image coming soon</span></div>
        @endif
        @if ($badge)
            <span class="absolute left-3 top-3 z-[2]"><x-badge tone="{{ $product->is_demo && ! $product->is_raw_leather && ! $product->is_custom ? 'neutral' : 'accent' }}" class="bg-surface/95 shadow-sm backdrop-blur">{{ $badge }}</x-badge></span>
        @endif
        <span class="absolute bottom-3 right-3 z-[2] flex h-10 w-10 translate-y-2 items-center justify-center rounded-full bg-primary text-on-primary opacity-0 shadow-md transition duration-300 group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:translate-y-0 group-focus-within:opacity-100" aria-hidden="true">
            <x-icon name="arrow-right" class="h-4 w-4"/>
        </span>
    </div>
    <div class="mt-4 flex flex-1 flex-col">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-clay">{{ $product->category?->name }}</p>
        <h3 class="h-card mt-1.5 line-clamp-2 sm:text-[1.375rem]">
            <a href="{{ route('products.show', $product) }}" class="transition-colors after:absolute after:inset-0 after:z-[1] group-hover:text-accent" aria-label="View and quote {{ $product->name }}">{{ $product->name }}</a>
        </h3>
        <p class="pc-meta mt-1.5 text-xs text-muted tabular-nums">SKU {{ $product->sku }} · MOQ {{ $product->moq }}</p>
        <div class="mt-auto pt-3.5"><div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 border-t border-border pt-3">
            <p class="text-sm">
                @if ($price['cents'] !== null)
                    <span class="text-muted">{{ $price['label'] }}</span> <span class="font-semibold tabular-nums">{{ money($price['cents']) }}</span>
                @else
                    <span class="font-medium">{{ $price['label'] }}</span>
                @endif
            </p>
            <span class="inline-flex items-center gap-1.5 text-xs text-muted"><span class="dot {{ $dot }}" aria-hidden="true"></span>{{ $product->availability->label() }}</span>
        </div></div>
        @if ($price['note'])<p class="mt-1 text-xs font-medium text-accent">{{ $price['note'] }}</p>@endif
    </div>
</article>
