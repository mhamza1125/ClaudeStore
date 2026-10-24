{{--
    Homepage banner slider (slides managed in Admin → Homepage slides). Crossfade with a
    subtle zoom and staggered copy, autoplay with a progress indicator, pause on hover/focus,
    previous/next, dots, arrow keys and swipe. Inactive slides are inert. Reduced motion:
    no autoplay and no animation.
--}}
@props(['slides', 'autoplay' => 6])
@php $count = $slides->count(); @endphp
<section class="border-b border-border bg-elevated/50"
         x-data="heroBanner({{ $count }}, {{ max(0, (int) $autoplay) }})" style="--hero-interval: {{ max(1, (int) $autoplay) }}s"
         @mouseenter="hover = true" @mouseleave="hover = false"
         @focusin="focused = true" @focusout="focused = $el.contains($event.relatedTarget)"
         @keydown.arrow-right="next()" @keydown.arrow-left="prev()"
         role="region" aria-roledescription="carousel" aria-label="Highlights" data-testid="hero-banner">
    <div class="container-page pb-6 pt-5 sm:pt-6 lg:pb-8 lg:pt-8">
        <div class="hero-stack" @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">
            @foreach ($slides as $i => $slide)
                @php $image = $slide->imageUrl(); $tag = $i === 0 ? 'h1' : 'h2'; @endphp
                <div class="hero-slide grid items-center gap-6 md:grid-cols-2 lg:gap-12 {{ $i === 0 ? 'is-active' : '' }}"
                     :class="{ 'is-active': active === {{ $i }} }" x-effect="$el.inert = active !== {{ $i }}" @if ($i > 0) inert @endif
                     role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $count }}: {{ $slide->title }}" data-slide="{{ $i }}">
                    <div class="hero-copy order-2 md:order-1">
                        @if ($slide->eyebrow)<p class="eyebrow mb-3 sm:mb-4">{{ $slide->eyebrow }}</p>@endif
                        <{{ $tag }} class="text-[2rem] leading-[1.08] sm:text-5xl lg:text-6xl">{{ $slide->title }}</{{ $tag }}>
                        @if ($slide->subtitle)<p class="mt-3 line-clamp-3 max-w-xl text-[15px] text-muted sm:mt-4 sm:line-clamp-none sm:text-lg">{{ $slide->subtitle }}</p>@endif
                        @if ($slide->primary_label || $slide->secondary_label)
                            <div class="mt-5 flex flex-wrap gap-2 sm:mt-8 sm:gap-3">
                                @if ($slide->primary_label && $slide->primary_url)
                                    <a href="{{ $slide->primary_url }}" class="btn btn-primary sm:px-7 sm:py-3.5 sm:text-base">{{ $slide->primary_label }} <x-icon name="arrow-right" class="h-4 w-4"/></a>
                                @endif
                                @if ($slide->secondary_label && $slide->secondary_url)
                                    <a href="{{ $slide->secondary_url }}" class="btn btn-secondary sm:px-7 sm:py-3.5 sm:text-base">{{ $slide->secondary_label }}</a>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="order-1 md:order-2">
                        <div class="hero-media relative aspect-[16/10] overflow-hidden sm:aspect-[4/3] rounded-md bg-elevated shadow-sm">
                            @if ($image)
                                <img src="{{ $image }}" alt="{{ $slide->imageAlt() }}" width="1200" height="900"
                                     class="absolute inset-0 h-full w-full object-cover" style="object-position: {{ array_key_exists($slide->image_focus, \App\Models\HeroSlide::FOCUS) ? $slide->image_focus : 'center' }}"
                                     @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                            @endif
                            @if ($slide->product && $slide->product->isPublished())
                                <a href="{{ route('products.show', $slide->product) }}" class="absolute bottom-3 left-3 max-w-[calc(100%-11.5rem)] truncate rounded-sm bg-surface/90 px-3 py-1.5 text-xs font-medium text-text shadow-sm backdrop-blur hover:text-accent md:max-w-[75%]">
                                    {{ $slide->product->name }} <x-icon name="arrow-right" class="inline h-3 w-3"/>
                                </a>
                            @endif
                            @if ($count > 1)
                                {{-- Phones: controls sit on the image, clear of the floating buttons at the screen's bottom corners. --}}
                                <div class="absolute bottom-3 right-3 flex items-center gap-1 rounded-full bg-surface/90 p-1 text-text shadow-sm backdrop-blur md:hidden">
                                    <button type="button" @click="prev()" class="flex h-8 w-8 items-center justify-center rounded-full hover:bg-elevated" aria-label="Previous slide"><x-icon name="chevron-left" class="h-4 w-4"/></button>
                                    <span class="px-1 text-xs tabular-nums" aria-hidden="true">{{ $i + 1 }}/{{ $count }}</span>
                                    <button type="button" @click="paused = !paused" x-show="interval > 0" class="flex h-8 w-8 items-center justify-center rounded-full hover:bg-elevated" :aria-label="paused ? 'Play slideshow' : 'Pause slideshow'">
                                        <svg x-show="!paused" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                                        <svg x-show="paused" x-cloak class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5v14l12-7z"/></svg>
                                    </button>
                                    <button type="button" @click="next()" class="flex h-8 w-8 items-center justify-center rounded-full hover:bg-elevated" aria-label="Next slide"><x-icon name="chevron-right" class="h-4 w-4"/></button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($count > 1)
            <div class="mt-5 hidden items-center justify-between gap-4 md:flex">
                <div class="flex items-center gap-4">
                    <p class="font-serif text-lg tabular-nums text-muted" aria-hidden="true"><span x-text="String(active + 1).padStart(2, '0')">01</span> / {{ str_pad((string) $count, 2, '0', STR_PAD_LEFT) }}</p>
                    <div class="flex items-center gap-2" role="group" aria-label="Choose slide">
                        @foreach ($slides as $i => $slide)
                            <button type="button" @click="go({{ $i }})" :aria-current="active === {{ $i }}" aria-label="Show slide {{ $i + 1 }}: {{ $slide->title }}"
                                    class="relative h-1.5 overflow-hidden rounded-full bg-border-strong/60 transition-all duration-300" :class="active === {{ $i }} ? 'w-10' : 'w-4 hover:bg-muted'">
                                <span x-show="active === {{ $i }}" class="hero-progress absolute inset-0 rounded-full bg-accent"
                                      :class="interval ? (isPaused() ? 'is-running is-paused' : 'is-running') : 'is-static'" @animationend="next(true)"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" @click="paused = !paused" x-show="interval > 0" class="btn btn-ghost btn-sm !px-2" :aria-label="paused ? 'Play slideshow' : 'Pause slideshow'">
                        <span x-show="!paused"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg></span>
                        <span x-show="paused" x-cloak><svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5v14l12-7z"/></svg></span>
                    </button>
                    <button type="button" @click="prev()" class="btn btn-secondary btn-sm !px-2" aria-label="Previous slide"><x-icon name="chevron-left" class="h-4 w-4"/></button>
                    <button type="button" @click="next()" class="btn btn-secondary btn-sm !px-2" aria-label="Next slide"><x-icon name="chevron-right" class="h-4 w-4"/></button>
                </div>
            </div>
        @endif
    </div>
</section>
