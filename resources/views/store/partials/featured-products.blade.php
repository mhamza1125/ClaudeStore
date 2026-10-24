{{-- Featured products carousel: intro and controls on the left, a scroll-snap track on the right. --}}
@props(['products', 'autoplay' => 5])
@if ($products->isNotEmpty())
    <section class="container-page py-14 lg:py-20" aria-labelledby="featured-heading"
             x-data="productCarousel({{ max(0, (int) $autoplay) }})" data-testid="featured-carousel">
        <div class="grid gap-8 lg:grid-cols-12 lg:items-center lg:gap-10">
            <div class="lg:col-span-4 xl:col-span-3">
                <p class="eyebrow mb-2">Featured products</p>
                <h2 id="featured-heading" class="text-4xl">Trade favourites</h2>
                <p class="mt-3 text-muted">Our most requested styles, with volume pricing from the first case. Add any of them to a quote with your quantities.</p>
                <div class="mt-6 flex items-center gap-2">
                    <button type="button" @click="prev()" class="btn btn-secondary !px-3" aria-label="Previous products" aria-controls="featured-track"><x-icon name="chevron-left" class="h-4 w-4"/></button>
                    <button type="button" @click="next()" class="btn btn-secondary !px-3" aria-label="Next products" aria-controls="featured-track"><x-icon name="chevron-right" class="h-4 w-4"/></button>
                    <a href="{{ route('catalog') }}" class="link ml-3 text-sm">View all products</a>
                </div>
            </div>
            <div class="min-w-0 lg:col-span-8 xl:col-span-9"
                 @mouseenter="hover = true" @mouseleave="hover = false"
                 @focusin="focused = true" @focusout="focused = $el.contains($event.relatedTarget)"
                 @touchstart.passive="hold()" @pointerdown="hold()" @wheel.passive="hold()">
                <ul id="featured-track" x-ref="track" class="no-scrollbar -mx-1 flex snap-x snap-mandatory gap-5 overflow-x-auto px-1 pb-1" role="list" aria-label="Featured products">
                    @foreach ($products as $product)
                        <li data-card class="w-[78%] shrink-0 snap-start sm:w-[calc((100%-1.25rem)/2)] xl:w-[calc((100%-2.5rem)/3)]">
                            <x-product-card :product="$product" :compact="true"/>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-5 h-0.5 overflow-hidden rounded-full bg-border" aria-hidden="true">
                    <div class="h-full origin-left bg-accent transition-transform duration-300" :style="`transform: scaleX(${Math.max(progress, 0.08)})`"></div>
                </div>
            </div>
        </div>
    </section>
@endif
