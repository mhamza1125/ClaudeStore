<x-layouts.store :title="$product->meta_title ?: $product->name" :description="$product->meta_description ?: $product->short_description" :canonical="route('products.show', $product)">
    @push('head')
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endpush
    <div class="container-page py-10">
        <nav class="mb-6 text-xs text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-text">Home</a> /
            <a href="{{ route('catalog') }}" class="hover:text-text">Products</a>
            @if ($product->category?->parent) / <a href="{{ route('catalog.category', $product->category->parent) }}" class="hover:text-text">{{ $product->category->parent->name }}</a>@endif
            @if ($product->category) / <a href="{{ route('catalog.category', $product->category) }}" class="hover:text-text">{{ $product->category->name }}</a>@endif
        </nav>

        @unless ($product->isPublished())
            <div class="mb-6 badge badge-warning w-full justify-start px-4 py-3 text-sm">Staff preview — this product is {{ $product->status }} and not visible to customers.</div>
        @endunless

        <livewire:store.product-purchase :product="$product" :is-favorite="$isFavorite"/>

        {{-- Details --}}
        <div class="mt-16 grid gap-12 border-t border-border pt-14 lg:mt-24 lg:grid-cols-12">
            <section class="lg:col-span-7" aria-labelledby="desc-heading">
                <h2 id="desc-heading" class="h-product mb-5">Description</h2>
                <div class="prose-store whitespace-pre-line text-muted">{{ $product->description ?: $product->short_description }}</div>
            </section>
            <section class="lg:col-span-5" aria-labelledby="spec-heading">
                <h2 id="spec-heading" class="h-product mb-5">Specifications</h2>
                @php
                    $specs = array_filter([
                        'Material' => $product->material, 'Leather type' => $product->leather_type, 'Dimensions' => $product->dimensions,
                        'Hardware' => $product->hardware, 'Finishing' => $product->finishing, 'Packaging' => $product->packaging,
                        'Production / lead time' => $product->lead_time,
                    ]);
                    foreach ($product->specifications ?? [] as $row) { if (filled($row['label'] ?? null) && filled($row['value'] ?? null)) $specs[$row['label']] = $row['value']; }
                @endphp
                @if ($specs)
                    <dl class="divide-y divide-border border-y border-border text-sm">
                        @foreach ($specs as $label => $value)
                            <div class="grid grid-cols-5 gap-4 py-3"><dt class="col-span-2 text-muted">{{ $label }}</dt><dd class="col-span-3">{{ $value }}</dd></div>
                        @endforeach
                        <div class="grid grid-cols-5 gap-4 py-3"><dt class="col-span-2 text-muted">Minimum order</dt><dd class="col-span-3">{{ $product->moq }} units @if ($product->order_multiple > 1)(in multiples of {{ $product->order_multiple }})@endif</dd></div>
                    </dl>
                @else
                    <p class="text-sm text-muted">Specifications for this product have not been published. Ask us for a specification sheet.</p>
                @endif
                @if ($product->is_demo)
                    <p class="mt-4 text-xs text-muted">Demonstration product: these values are illustrative and unverified.</p>
                @endif
                @if ($product->spec_sheet_path)
                    <a href="{{ route('products.spec-sheet', $product) }}" class="btn btn-secondary mt-6"><x-icon name="download" class="h-4 w-4"/> Download specification sheet</a>
                @endif
            </section>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-20 border-t border-border pt-14 lg:mt-24" aria-labelledby="related-heading">
                <h2 id="related-heading" class="h-section mb-10">Related products</h2>
                <div class="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                    @foreach ($related as $item)<x-product-card :product="$item"/>@endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.store>
