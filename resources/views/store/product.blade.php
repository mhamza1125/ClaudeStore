@php
    $ogImage = $product->approvedImages->first()?->url('large');
    $crumbs = array_values(array_filter([
        ['Home', route('home')],
        ['Products', route('catalog')],
        $product->category?->parent ? [$product->category->parent->name, route('catalog.category', $product->category->parent)] : null,
        $product->category ? [$product->category->name, route('catalog.category', $product->category)] : null,
        [$product->name, route('products.show', $product)],
    ]));
@endphp
<x-layouts.store :title="$product->meta_title ?: $product->name" :description="$product->meta_description ?: $product->short_description" :canonical="route('products.show', $product)" :og-image="$ogImage" og-type="product" :noindex="! $product->isPublished()">
    @push('head')
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endpush
    <div class="container-page pb-16 pt-6 sm:pt-8">
        <x-breadcrumbs :items="$crumbs" class="mb-6 sm:mb-8"/>

        @unless ($product->isPublished())
            <div class="alert badge-warning mb-6"><x-icon name="shield" class="h-4 w-4 shrink-0"/>Staff preview — this product is {{ $product->status }} and not visible to customers.</div>
        @endunless

        <livewire:store.product-purchase :product="$product" :is-favorite="$isFavorite"/>

        {{-- Details --}}
        <div class="mt-16 grid gap-10 lg:mt-24 lg:grid-cols-12 lg:gap-14">
            <section class="lg:col-span-7" aria-labelledby="desc-heading">
                <p class="eyebrow eyebrow-stitch mb-4">The product</p>
                <h2 id="desc-heading" class="h-product mb-6">Description</h2>
                <div class="prose-store whitespace-pre-line text-muted">{{ trim((string) ($product->description ?: $product->short_description)) }}</div>
            </section>
            <section class="lg:col-span-5" aria-labelledby="spec-heading">
                @php
                    $specs = array_filter([
                        'Material' => $product->material, 'Leather type' => $product->leather_type, 'Dimensions' => $product->dimensions,
                        'Hardware' => $product->hardware, 'Finishing' => $product->finishing, 'Packaging' => $product->packaging,
                        'Production / lead time' => $product->lead_time,
                    ]);
                    foreach ($product->specifications ?? [] as $row) { if (filled($row['label'] ?? null) && filled($row['value'] ?? null)) $specs[$row['label']] = $row['value']; }
                @endphp
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between gap-4 bg-elevated px-6 py-4">
                        <h2 id="spec-heading" class="font-sans text-xs font-semibold uppercase tracking-[0.16em] text-muted">Specifications</h2>
                        <span class="text-xs text-muted tabular-nums">{{ $product->sku }}</span>
                    </div>
                    @if ($specs)
                        <dl class="divide-y divide-border text-sm">
                            @foreach ($specs as $label => $value)
                                <div class="grid grid-cols-5 gap-4 px-6 py-3.5"><dt class="col-span-2 text-muted">{{ $label }}</dt><dd class="col-span-3">{{ $value }}</dd></div>
                            @endforeach
                            <div class="grid grid-cols-5 gap-4 px-6 py-3.5"><dt class="col-span-2 text-muted">Minimum order</dt><dd class="col-span-3 font-medium">{{ $product->moq }} units @if ($product->order_multiple > 1)<span class="font-normal text-muted">(in multiples of {{ $product->order_multiple }})</span>@endif</dd></div>
                        </dl>
                    @else
                        <p class="px-6 py-5 text-sm text-muted">Specifications for this product have not been published. Ask us for a specification sheet.</p>
                    @endif
                    @if ($product->is_demo || $product->spec_sheet_path)
                        <div class="border-t border-border px-6 py-4">
                            @if ($product->is_demo)<p class="text-xs text-muted">Demonstration product: these values are illustrative and unverified.</p>@endif
                            @if ($product->spec_sheet_path)
                                <a href="{{ route('products.spec-sheet', $product) }}" class="btn btn-secondary btn-sm {{ $product->is_demo ? 'mt-3' : '' }}"><x-icon name="download" class="h-4 w-4"/> Download specification sheet</a>
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-20 border-t border-border pt-14 lg:mt-24" aria-labelledby="related-heading">
                <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow eyebrow-stitch mb-4">More in {{ $product->category?->name ?? 'the collection' }}</p>
                        <h2 id="related-heading" class="h-section">Related products</h2>
                    </div>
                    @if ($product->category)<a href="{{ route('catalog.category', $product->category) }}" class="btn btn-secondary">View all <x-icon name="arrow-right" class="h-4 w-4"/></a>@endif
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                    @foreach ($related as $item)<x-product-card :product="$item"/>@endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.store>
