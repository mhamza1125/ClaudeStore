{{-- Featured products: ONE section with optional tabs (Featured / Bestsellers / New arrivals).
     Each product appears in a single tab (see HomeController::productTabs). Every tab is a
     native scroll-snap track with arrows and a progress bar; tabs show only when 2+ have products. --}}
@props(['tabs' => [], 'autoplay' => 5])
@if (count($tabs))
    <section class="border-y border-border bg-elevated/60" aria-labelledby="featured-heading" data-testid="featured-carousel" x-data="{ tab: '{{ $tabs[0]['key'] }}' }">
        <div class="container-page section-y">
            <div class="mb-10 flex flex-wrap items-end justify-between gap-x-8 gap-y-6 lg:mb-12">
                <div class="max-w-xl">
                    <p class="eyebrow eyebrow-stitch mb-4">Trade favourites</p>
                    <h2 id="featured-heading" class="h-section">Most requested styles</h2>
                    <p class="mt-4 text-muted">Volume pricing from the first case. Add any of them to a quote with your quantities.</p>
                </div>
                @if (count($tabs) > 1)
                    <div class="inline-flex rounded-full border border-border bg-surface p-1 shadow-sm" role="tablist" aria-label="Product groups">
                        @foreach ($tabs as $t)
                            <button type="button" role="tab" id="tab-{{ $t['key'] }}" aria-controls="panel-{{ $t['key'] }}" :aria-selected="tab === '{{ $t['key'] }}'" @click="tab = '{{ $t['key'] }}'"
                                    class="rounded-full px-4 py-2 text-sm font-medium transition-colors" :class="tab === '{{ $t['key'] }}' ? 'bg-band text-on-band' : 'text-muted hover:text-text'">{{ $t['label'] }}</button>
                        @endforeach
                    </div>
                @endif
            </div>

            @foreach ($tabs as $t)
                <div id="panel-{{ $t['key'] }}" role="tabpanel" @if (count($tabs) > 1) aria-labelledby="tab-{{ $t['key'] }}" x-show="tab === '{{ $t['key'] }}'" x-transition.opacity.duration.200ms @if (! $loop->first) x-cloak @endif @endif
                     x-data="productCarousel({{ max(0, (int) $autoplay) }})"
                     @mouseenter="hover = true" @mouseleave="hover = false"
                     @focusin="focused = true" @focusout="focused = $el.contains($event.relatedTarget)"
                     @touchstart.passive="hold()" @pointerdown="hold()" @wheel.passive="hold()">
                    <ul id="track-{{ $t['key'] }}" x-ref="track" class="no-scrollbar -mx-1 flex snap-x snap-mandatory gap-5 overflow-x-auto px-1 pb-2" role="list" aria-label="{{ $t['label'] }} products">
                        @foreach ($t['products'] as $product)
                            <li data-card class="w-[72%] shrink-0 snap-start sm:w-[calc((100%-1.25rem)/2)] md:w-[calc((100%-2.5rem)/3)] xl:w-[calc((100%-3.75rem)/4)]">
                                <x-product-card :product="$product" :compact="true"/>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-8 flex items-center gap-5">
                        <div class="h-0.5 flex-1 overflow-hidden rounded-full bg-border" aria-hidden="true">
                            <div class="h-full origin-left rounded-full bg-band transition-transform duration-300" :style="`transform: scaleX(${Math.max(progress, 0.08)})`"></div>
                        </div>
                        <a href="{{ route('catalog', $t['key'] === 'new' ? ['sort' => 'newest'] : []) }}" class="link-arrow hidden sm:inline-flex">View all products <x-icon name="arrow-right" class="h-4 w-4"/></a>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="prev()" class="btn btn-secondary btn-icon" aria-label="Previous products" aria-controls="track-{{ $t['key'] }}"><x-icon name="chevron-left" class="h-4 w-4"/></button>
                            <button type="button" @click="next()" class="btn btn-secondary btn-icon" aria-label="Next products" aria-controls="track-{{ $t['key'] }}"><x-icon name="chevron-right" class="h-4 w-4"/></button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
