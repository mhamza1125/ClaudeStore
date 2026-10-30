{{-- Product card. "compact" (carousels): 4:3 image, fixed title height and a bottom-aligned
     action so neighbouring cards line up; default (grids): 4:5 image. --}}
@props(['product', 'compact' => false])
@php
    $image = $product->primaryImage();
    $price = app(\App\Services\CatalogPricing::class)->summary($product, app(\App\Pricing\PriceAccess::class)->current());
    $aspect = $compact ? 'aspect-[4/3]' : 'aspect-[4/5]';
@endphp
<article {{ $attributes->merge(['class' => 'group flex flex-col'.($compact ? ' h-full rounded-md border border-border bg-surface p-3 transition hover:border-border-strong hover:shadow-md' : '')]) }}>
    <a href="{{ route('products.show', $product) }}" class="relative block overflow-hidden rounded-sm bg-elevated" @if ($compact) tabindex="-1" aria-hidden="true" @endif>
        @if ($image)
            <img src="{{ $image->url('card') }}" alt="{{ $image->alt ?? $product->name }}" loading="lazy" width="800" height="{{ $compact ? 600 : 1000 }}" draggable="false"
                 class="protected-img {{ $aspect }} w-full object-cover transition duration-500 group-hover:scale-[1.03]">
            <x-image-watermark :size="$compact ? 'sm' : 'md'"/>
        @else
            <div class="flex {{ $aspect }} items-center justify-center text-muted"><x-icon name="image" class="h-10 w-10"/></div>
        @endif
        <div class="absolute left-3 top-3 flex flex-col items-start gap-1">
            @if ($product->is_custom)<x-badge tone="accent">Custom</x-badge>@endif
            @if ($product->is_demo)<x-badge>Demo product</x-badge>@endif
        </div>
    </a>
    <div class="mt-4 flex flex-1 flex-col {{ $compact ? 'px-1 pb-1' : '' }}">
        <p class="text-xs uppercase tracking-wider text-muted">{{ $product->category?->name }}</p>
        <h3 class="mt-1 font-serif text-xl leading-snug {{ $compact ? 'line-clamp-2 min-h-[2.75em]' : '' }}"><a href="{{ route('products.show', $product) }}" class="hover:text-accent">{{ $product->name }}</a></h3>
        <p class="mt-0.5 text-xs text-muted">SKU {{ $product->sku }} · MOQ {{ $product->moq }}</p>
        <div class="mt-3 flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1 {{ $compact ? 'mt-auto pt-3' : '' }}">
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
        @if ($compact)
            <a href="{{ route('products.show', $product) }}" class="btn btn-secondary btn-sm mt-3 w-full" aria-label="View and quote {{ $product->name }}">View &amp; quote</a>
        @endif
    </div>
</article>
