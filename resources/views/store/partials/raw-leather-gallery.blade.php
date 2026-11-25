{{-- Raw leather strip: products flagged "Raw leather" in admin. Raw leather is never sold at a
     fixed price; every tile leads to the product page, where it is added to a quote.
     The first tile is shown large; up to five are displayed. --}}
@props(['products'])
@if ($products->isNotEmpty())
    <section class="section-y container-page" aria-labelledby="raw-heading" data-testid="raw-leather-gallery">
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-14">
            <div class="lg:col-span-4">
                <p class="eyebrow eyebrow-stitch mb-4">For workshops &amp; makers</p>
                <h2 id="raw-heading" class="h-section">Raw leather</h2>
                <p class="mt-5 text-muted">Hides, sides and skins for workshops, manufacturers and brands. Grade, thickness, colour and finish vary from lot to lot, so raw leather is priced by quotation.</p>
                <ul class="mt-7 space-y-3 text-sm">
                    @foreach (['Tell us the type, thickness and quantity you need', 'Swatches and samples available on request', 'Quoted by square foot or by hide'] as $point)
                        <li class="flex gap-3"><span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-accent-soft text-accent"><x-icon name="check" class="h-3 w-3"/></span>{{ $point }}</li>
                    @endforeach
                </ul>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('catalog', ['raw' => 1]) }}" class="btn btn-primary">Browse raw leather <x-icon name="arrow-right" class="h-4 w-4"/></a>
                    <a href="{{ route('contact') }}" class="btn btn-secondary">Ask for a quote</a>
                </div>
            </div>

            <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:col-span-8 lg:grid-cols-4" role="list">
                @foreach ($products->take(5) as $product)
                    @php
                        $image = $product->primaryImage();
                        $facts = array_filter([$product->leather_type, $product->finishing]);
                    @endphp
                    <li @class(['col-span-2 row-span-2' => $loop->first])>
                        <a href="{{ route('products.show', $product) }}"
                           class="tile {{ $loop->first ? 'aspect-square lg:aspect-auto lg:h-full' : 'aspect-square' }}"
                           aria-label="{{ $product->name }}: request a quote">
                            @if ($image)
                                <img src="{{ $image->url('card') }}" alt="" loading="lazy" draggable="false"
                                     class="protected-img absolute inset-0 h-full w-full object-cover motion-reduce:transition-none">
                                <x-image-watermark :size="$loop->first ? 'md' : 'sm'"/>
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-muted"><x-icon name="image" class="h-10 w-10"/></div>
                            @endif
                            <div class="tile-label !p-3 sm:!p-4">
                                <div class="min-w-0">
                                    <h3 class="truncate font-serif leading-tight {{ $loop->first ? 'text-2xl' : 'text-base' }}">{{ $product->name }}</h3>
                                    @if ($facts)<p class="mt-0.5 line-clamp-1 text-xs text-muted">{{ implode(' · ', $facts) }}</p>@endif
                                    @if ($loop->first)<p class="mt-2 text-xs font-semibold text-accent">Request a quote</p>@endif
                                </div>
                                @if ($loop->first)<span class="tile-arrow" aria-hidden="true"><x-icon name="arrow-right" class="h-4 w-4"/></span>@endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
