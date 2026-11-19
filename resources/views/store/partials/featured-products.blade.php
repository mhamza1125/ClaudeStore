{{-- Featured products: ONE section with optional tabs (Featured / Bestsellers / New arrivals).
     Each product appears in a single tab (see HomeController::productTabs). Every tab is a
     native scroll-snap track with arrows and a progress bar; tabs show only when 2+ have products. --}}
@props(['tabs' => [], 'autoplay' => 5])
@if (count($tabs))
    <section class="section-y container-page" aria-labelledby="featured-heading" data-testid="featured-carousel" x-data="{ tab: '{{ $tabs[0]['key'] }}' }">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-x-8 gap-y-5 lg:mb-10">
            <div>
                <h2 id="featured-heading" class="h-section">Trade favourites</h2>
                <p class="mt-3 max-w-xl text-muted">Our most requested styles, with volume pricing from the first case. Add any of them to a quote with your quantities.</p>
            </div>
            @if (count($tabs) > 1)
                <div class="flex gap-6 border-b border-border" role="tablist" aria-label="Product groups">
                    @foreach ($tabs as $t)
                        <button type="button" role="tab" id="tab-{{ $t['key'] }}" aria-controls="panel-{{ $t['key'] }}" :aria-selected="tab === '{{ $t['key'] }}'" @click="tab = '{{ $t['key'] }}'"
                                class="-mb-px border-b-2 pb-3 text-sm transition-colors" :class="tab === '{{ $t['key'] }}' ? 'border-brass font-medium text-text' : 'border-transparent text-muted hover:text-text'">{{ $t['label'] }}</button>
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
                <ul id="track-{{ $t['key'] }}" x-ref="track" class="no-scrollbar -mx-1 flex snap-x snap-mandatory gap-5 overflow-x-auto px-1 pb-1" role="list" aria-label="{{ $t['label'] }} products">
                    @foreach ($t['products'] as $product)
                        <li data-card class="w-[68%] shrink-0 snap-start sm:w-[calc((100%-1.25rem)/2)] md:w-[calc((100%-2.5rem)/3)] xl:w-[calc((100%-3.75rem)/4)]">
                            <x-product-card :product="$product" :compact="true"/>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6 flex items-center gap-5">
                    <div class="h-px flex-1 overflow-hidden bg-border" aria-hidden="true">
                        <div class="h-full origin-left bg-brass transition-transform duration-300" :style="`transform: scaleX(${Math.max(progress, 0.08)})`"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="prev()" class="btn btn-secondary btn-sm !px-2.5" aria-label="Previous products" aria-controls="track-{{ $t['key'] }}"><x-icon name="chevron-left" class="h-4 w-4"/></button>
                        <button type="button" @click="next()" class="btn btn-secondary btn-sm !px-2.5" aria-label="Next products" aria-controls="track-{{ $t['key'] }}"><x-icon name="chevron-right" class="h-4 w-4"/></button>
                    </div>
                    <a href="{{ route('catalog', $t['key'] === 'new' ? ['sort' => 'newest'] : []) }}" class="link hidden text-sm sm:inline">View all products</a>
                </div>
            </div>
        @endforeach
    </section>
@endif
