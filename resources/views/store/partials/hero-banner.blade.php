{{--
    Homepage hero slider (slides managed in Admin → Homepage slides). Editorial split: a large
    headline with one walnut call to action on the left; the image in a stitched frame over a cream
    block that runs off the right edge. Crossfade with a slow zoom and staggered copy, autoplay with
    a progress indicator, pause on hover/focus, previous/next, dots, arrow keys and swipe. Inactive
    slides are inert. Reduced motion: no autoplay and no animation.
--}}
@props(['slides', 'autoplay' => 6])
@php $count = $slides->count(); @endphp
<section class="relative overflow-hidden"
         x-data="heroBanner({{ $count }}, {{ max(0, (int) $autoplay) }})" style="--hero-interval: {{ max(1, (int) $autoplay) }}s"
         @mouseenter="hover = true" @mouseleave="hover = false"
         @focusin="focused = true" @focusout="focused = $el.contains($event.relatedTarget)"
         @keydown.arrow-right="next()" @keydown.arrow-left="prev()"
         role="region" aria-roledescription="carousel" aria-label="Highlights" data-testid="hero-banner">
    <div class="hero-backdrop" aria-hidden="true"></div>
    <div class="container-page relative pb-10 pt-6 sm:pt-10 lg:pb-14 lg:pt-14">
        <div class="hero-stack" @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">
            @foreach ($slides as $i => $slide)
                @php $image = $slide->imageUrl(); $tag = $i === 0 ? 'h1' : 'h2'; @endphp
                <div class="hero-slide grid items-center gap-7 md:grid-cols-12 lg:gap-14 {{ $i === 0 ? 'is-active' : '' }}"
                     :class="{ 'is-active': active === {{ $i }} }" x-effect="$el.inert = active !== {{ $i }}" @if ($i > 0) inert @endif
                     role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $count }}: {{ $slide->title }}" data-slide="{{ $i }}">
                    <div class="hero-copy order-2 md:order-1 md:col-span-6">
                        @if ($slide->eyebrow)
                            <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-border bg-surface px-3 py-1.5 text-[10.5px] font-semibold uppercase tracking-[0.1em] sm:px-3.5 sm:text-[11.5px] sm:tracking-[0.14em] text-clay shadow-sm sm:mb-7">
                                <span class="dot bg-primary" aria-hidden="true"></span>{{ $slide->eyebrow }}
                            </p>
                        @endif
                        <{{ $tag }} class="h-hero max-w-[12ch] text-balance">{{ $slide->title }}</{{ $tag }}>
                        @if ($slide->subtitle)<p class="mt-5 line-clamp-3 max-w-xl text-base leading-relaxed text-muted sm:mt-6 sm:line-clamp-none sm:text-lg">{{ $slide->subtitle }}</p>@endif
                        @if ($slide->primary_label || $slide->secondary_label)
                            <div class="mt-7 flex flex-wrap gap-3 sm:mt-9">
                                @if ($slide->primary_label && $slide->primary_url)
                                    <a href="{{ $slide->primary_url }}" class="btn btn-primary btn-lg w-full sm:w-auto">{{ $slide->primary_label }} <x-icon name="arrow-right" class="h-4 w-4"/></a>
                                @endif
                                @if ($slide->secondary_label && $slide->secondary_url)
                                    <a href="{{ $slide->secondary_url }}" class="btn btn-secondary btn-lg w-full sm:w-auto">{{ $slide->secondary_label }}</a>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="order-1 md:order-2 md:col-span-6">
                        <div class="hero-media stitch-frame relative aspect-[4/3] overflow-hidden rounded-xl bg-elevated shadow-lg lg:aspect-[5/4]" style="--stitch-radius: 24px; --stitch-inset: 12px">
                            @if ($image)
                                <img src="{{ $image }}" alt="{{ $slide->imageAlt() }}" width="1200" height="900" draggable="false"
                                     class="protected-img absolute inset-0 h-full w-full object-cover" style="object-position: {{ array_key_exists($slide->image_focus, \App\Models\HeroSlide::FOCUS) ? $slide->image_focus : 'center' }}"
                                     @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                                {{-- Product photos only; uploaded banner artwork is not watermarked. --}}
                                @unless ($slide->image_path)<x-image-watermark size="lg"/>@endunless
                            @endif
                            @if ($slide->product && $slide->product->isPublished())
                                {{-- Featured product "hang tag". --}}
                                <a href="{{ route('products.show', $slide->product) }}" class="group absolute left-5 top-5 z-[3] flex max-w-[calc(100%-2.5rem)] items-center gap-3 rounded-full bg-surface/95 py-1.5 pl-1.5 pr-4 text-sm shadow-md backdrop-blur transition hover:bg-surface">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-band text-on-band"><x-icon name="tag" class="h-4 w-4"/></span>
                                    <span class="min-w-0"><span class="block text-[10.5px] font-semibold uppercase tracking-[0.12em] text-muted">Featured</span><span class="block truncate font-medium group-hover:text-accent">{{ $slide->product->name }}</span></span>
                                </a>
                            @endif
                            @if ($count > 1)
                                {{-- Phones: controls sit on the image, clear of the floating buttons at the screen's bottom corners. --}}
                                <div class="absolute bottom-4 right-4 z-[3] flex items-center gap-1 rounded-full bg-surface/95 p-1 text-text shadow-md backdrop-blur md:hidden">
                                    <button type="button" @click="prev()" class="flex h-9 w-9 items-center justify-center rounded-full hover:bg-elevated" aria-label="Previous slide"><x-icon name="chevron-left" class="h-4 w-4"/></button>
                                    <span class="px-1 text-xs tabular-nums" aria-hidden="true">{{ $i + 1 }}/{{ $count }}</span>
                                    <button type="button" @click="paused = !paused" x-show="interval > 0" class="flex h-9 w-9 items-center justify-center rounded-full hover:bg-elevated" :aria-label="paused ? 'Play slideshow' : 'Pause slideshow'">
                                        <svg x-show="!paused" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                                        <svg x-show="paused" x-cloak class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5v14l12-7z"/></svg>
                                    </button>
                                    <button type="button" @click="next()" class="flex h-9 w-9 items-center justify-center rounded-full hover:bg-elevated" aria-label="Next slide"><x-icon name="chevron-right" class="h-4 w-4"/></button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Static row: trade facts (left) and slide controls (right). --}}
        <div class="mt-8 flex flex-col gap-6 border-t border-border pt-6 md:flex-row md:items-center md:justify-between lg:mt-12">
            <ul class="grid gap-x-8 gap-y-3 text-sm sm:grid-cols-3 md:flex md:flex-wrap" role="list" aria-label="Why trade buyers work with us">
                @foreach ([['chart', 'Price breaks on every product'], ['clock', 'Quotes within one business day'], ['pin', 'Made in Sialkot, Pakistan']] as [$icon, $fact])
                    <li class="flex items-center gap-2.5 text-text/85"><x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0 text-clay"/>{{ $fact }}</li>
                @endforeach
            </ul>
            @if ($count > 1)
                <div class="hidden shrink-0 items-center gap-5 md:flex">
                    <p class="font-serif text-lg tabular-nums text-muted" aria-hidden="true"><span class="text-text" x-text="String(active + 1).padStart(2, '0')">01</span> / {{ str_pad((string) $count, 2, '0', STR_PAD_LEFT) }}</p>
                    <div class="flex items-center gap-2" role="group" aria-label="Choose slide">
                        @foreach ($slides as $i => $slide)
                            <button type="button" @click="go({{ $i }})" :aria-current="active === {{ $i }}" aria-label="Show slide {{ $i + 1 }}: {{ $slide->title }}"
                                    class="relative h-1.5 overflow-hidden rounded-full bg-border-strong/70 transition-all duration-300" :class="active === {{ $i }} ? 'w-12' : 'w-4 hover:bg-muted'">
                                <span x-show="active === {{ $i }}" class="hero-progress absolute inset-0 rounded-full bg-accent"
                                      :class="interval ? (isPaused() ? 'is-running is-paused' : 'is-running') : 'is-static'" @animationend="next(true)"></span>
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="paused = !paused" x-show="interval > 0" class="btn btn-ghost btn-icon" :aria-label="paused ? 'Play slideshow' : 'Pause slideshow'">
                            <span x-show="!paused"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg></span>
                            <span x-show="paused" x-cloak><svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5v14l12-7z"/></svg></span>
                        </button>
                        <button type="button" @click="prev()" class="btn btn-secondary btn-icon" aria-label="Previous slide"><x-icon name="chevron-left" class="h-4 w-4"/></button>
                        <button type="button" @click="next()" class="btn btn-secondary btn-icon" aria-label="Next slide"><x-icon name="chevron-right" class="h-4 w-4"/></button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
