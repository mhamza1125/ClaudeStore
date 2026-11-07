{{-- Raw leather gallery: products flagged "Raw leather" in admin. Raw leather is never sold
     at a fixed price; every tile leads to the product page, where it is added to a quote.
     The first tile is shown large; up to five are displayed. --}}
@props(['products'])
@if ($products->isNotEmpty())
    <section class="border-t border-border" aria-labelledby="raw-heading" data-testid="raw-leather-gallery">
        <div class="container-page grid gap-10 py-20 lg:grid-cols-12">
            <div class="lg:col-span-4 xl:col-span-3">
                <p class="eyebrow mb-2">Raw materials</p>
                <h2 id="raw-heading" class="text-4xl">Raw leather</h2>
                <p class="mt-4 text-muted">Hides, sides and skins for workshops, manufacturers and brands. Grade, thickness, colour and finish vary from lot to lot, so raw leather is priced by quotation rather than sold online.</p>
                <ul class="mt-6 space-y-3 text-sm">
                    <li class="flex gap-3"><x-icon name="check" class="h-5 w-5 shrink-0 text-accent"/> Tell us the type, thickness and quantity you need</li>
                    <li class="flex gap-3"><x-icon name="check" class="h-5 w-5 shrink-0 text-accent"/> Swatches and samples available on request</li>
                    <li class="flex gap-3"><x-icon name="check" class="h-5 w-5 shrink-0 text-accent"/> Quoted by square foot or by hide</li>
                </ul>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('catalog', ['raw' => 1]) }}" class="btn btn-primary">Browse raw leather</a>
                    <a href="{{ route('contact') }}" class="btn btn-secondary">Ask for a quote</a>
                </div>
            </div>

            <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:col-span-8 lg:grid-cols-4 xl:col-span-9" role="list">
                @foreach ($products->take(5) as $product)
                    @php
                        $image = $product->primaryImage();
                        $facts = array_filter([$product->leather_type, $product->finishing]);
                    @endphp
                    <li @class(['col-span-2 row-span-2' => $loop->first])>
                        <a href="{{ route('products.show', $product) }}"
                           class="group relative block overflow-hidden rounded-sm bg-elevated {{ $loop->first ? 'aspect-square lg:aspect-auto lg:h-full' : 'aspect-square' }}"
                           aria-label="{{ $product->name }}: request a quote">
                            @if ($image)
                                <img src="{{ $image->url('card') }}" alt="" loading="lazy" draggable="false"
                                     class="protected-img absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105 motion-reduce:transition-none">
                                <x-image-watermark :size="$loop->first ? 'md' : 'sm'"/>
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-muted"><x-icon name="image" class="h-10 w-10"/></div>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3 pt-12 text-white sm:p-4 sm:pt-16">
                                <h3 class="font-serif leading-tight {{ $loop->first ? 'text-2xl sm:text-3xl' : 'text-base sm:text-lg' }}">{{ $product->name }}</h3>
                                @if ($facts)<p class="mt-0.5 line-clamp-1 text-xs text-white/80">{{ implode(' · ', $facts) }}</p>@endif
                                <p class="mt-2 inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wider">Request a quote <x-icon name="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"/></p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
