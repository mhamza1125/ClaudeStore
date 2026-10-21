@props(['product'])
@php
    $image = $product->primaryImage();
    $price = app(\App\Services\CatalogPricing::class)->summary($product, auth()->user()?->company);
@endphp
<article {{ $attributes->merge(['class' => 'group flex flex-col']) }}>
    <a href="{{ route('products.show', $product) }}" class="relative block overflow-hidden rounded-sm bg-elevated">
        @if ($image)
            <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $product->name }}" loading="lazy" width="800" height="1000"
                 class="aspect-[4/5] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <div class="flex aspect-[4/5] items-center justify-center text-muted"><x-icon name="image" class="h-10 w-10"/></div>
        @endif
        <div class="absolute left-3 top-3 flex flex-col items-start gap-1">
            @if ($product->is_custom)<x-badge tone="accent">Custom</x-badge>@endif
            @if ($product->is_demo)<x-badge>Demo product</x-badge>@endif
        </div>
    </a>
    <div class="mt-4 flex flex-1 flex-col">
        <p class="text-xs uppercase tracking-wider text-muted">{{ $product->category?->name }}</p>
        <h3 class="mt-1 font-serif text-xl leading-snug"><a href="{{ route('products.show', $product) }}" class="hover:text-accent">{{ $product->name }}</a></h3>
        <p class="mt-0.5 text-xs text-muted">SKU {{ $product->sku }} · MOQ {{ $product->moq }}</p>
        <div class="mt-3 flex items-baseline justify-between gap-2">
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
    </div>
</article>
