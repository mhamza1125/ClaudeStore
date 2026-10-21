{{--
    Hero image carousel. Only the image panel rotates; the headline and calls to action
    stay static beside it so they are always readable. Crossfade, pauses on hover/focus and
    in background tabs, honours prefers-reduced-motion, keyboard and dot navigation.
--}}
@props(['slides', 'autoplay' => 6])
@if ($slides->isNotEmpty())
    <div class="relative mx-auto max-w-md lg:ml-auto"
         x-data="heroCarousel({{ $slides->count() }}, {{ max(0, (int) $autoplay) }})"
         @mouseenter="hover = true" @mouseleave="hover = false"
         @focusin="focused = true" @focusout="focused = $el.contains($event.relatedTarget)"
         @keydown.arrow-right.prevent="next()" @keydown.arrow-left.prevent="prev()"
         role="region" aria-roledescription="carousel" aria-label="Featured products">
        <div class="absolute -inset-3 -z-10 translate-x-6 translate-y-6 rounded-sm border border-accent/40" aria-hidden="true"></div>

        <div class="relative aspect-[4/5] overflow-hidden rounded-sm bg-elevated" aria-live="polite">
            @foreach ($slides as $i => ['product' => $product, 'image' => $image])
                <a href="{{ route('products.show', $product) }}" data-slide="{{ $i }}"
                   class="absolute inset-0 block transition-opacity duration-700 ease-out motion-reduce:transition-none"
                   :class="active === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
                   :aria-hidden="active !== {{ $i }}" :tabindex="active === {{ $i }} ? 0 : -1"
                   role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $slides->count() }}: {{ $product->name }}"
                   @if ($i > 0) aria-hidden="true" tabindex="-1" @endif>
                    <img src="{{ $image->url() }}" alt="{{ $image->alt ?: $product->name }}" width="800" height="1000"
                         class="h-full w-full object-cover" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                    <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-overlay to-transparent px-5 pb-4 pt-12 text-on-overlay">
                        <span class="block text-xs uppercase tracking-[0.18em] opacity-80">{{ $product->category?->name ?? 'Featured' }}</span>
                        <span class="mt-0.5 block font-serif text-2xl leading-tight">{{ $product->name }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        @if ($slides->count() > 1)
            <div class="mt-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2" role="group" aria-label="Choose slide">
                    @foreach ($slides as $i => ['product' => $product])
                        <button type="button" @click="go({{ $i }})" class="h-1.5 rounded-full transition-all duration-300"
                                :class="active === {{ $i }} ? 'w-8 bg-accent' : 'w-3 bg-border-strong hover:bg-muted'"
                                :aria-current="active === {{ $i }}" aria-label="Show {{ $product->name }}"></button>
                    @endforeach
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" @click="toggle()" x-show="interval > 0" class="btn btn-ghost btn-sm !px-2 text-xs" :aria-label="paused ? 'Play slideshow' : 'Pause slideshow'">
                        <span x-text="paused ? 'Play' : 'Pause'">Pause</span>
                    </button>
                    <button type="button" @click="prev()" class="btn btn-secondary btn-sm !px-2" aria-label="Previous product"><x-icon name="chevron-left" class="h-4 w-4"/></button>
                    <button type="button" @click="next()" class="btn btn-secondary btn-sm !px-2" aria-label="Next product"><x-icon name="chevron-right" class="h-4 w-4"/></button>
                </div>
            </div>
        @endif

        @if ($slides->contains(fn ($s) => $s['product']->is_demo))
            <p class="mt-3 text-xs text-muted">Demo products shown with placeholder illustrations. Replace with photography in Admin → Products; mark products as “Featured” to choose what appears here.</p>
        @endif
    </div>
@endif
