<x-layouts.store :description="'Wholesale and private-label leather bags, wallets, belts and accessories. Volume pricing and fast quotations for trade buyers.'">
    {{-- Notices from redirects to home (e.g. "You have been signed out.") --}}
    @if (session('status') || session('warning') || session('error'))
        <div class="container-page pt-6"><x-flash/></div>
    @endif

    {{-- 1. Hero (Admin → Homepage slides) --}}
    @include('store.partials.hero-banner', ['slides' => $heroSlides, 'autoplay' => $heroAutoplaySeconds])

    {{-- 2. Trade advantages: one compact line, not four cards --}}
    <div class="border-b border-border bg-surface">
        <ul class="container-page grid grid-cols-2 divide-border py-5 text-sm sm:grid-cols-4 sm:divide-x" role="list">
            @foreach ([['box', 'Wholesale supply'], ['tag', 'Private label'], ['scissors', 'Custom manufacturing'], ['folder', 'Raw leather']] as [$icon, $label])
                <li class="flex items-center gap-3 px-1 py-2 sm:justify-center sm:px-4">
                    <x-icon :name="$icon" class="h-5 w-5 shrink-0 text-brass"/>
                    <span class="font-medium tracking-wide">{{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- 3. Explore collections (categories marked "featured" in Admin → Categories) --}}
    @if ($categories->isNotEmpty())
        @php $cols = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3'][$categories->count()] ?? 'lg:grid-cols-4'; @endphp
        <section class="section-y container-page" aria-labelledby="cat-heading">
            <div class="mb-10 flex flex-wrap items-end justify-between gap-4 lg:mb-14">
                <div>
                    <p class="eyebrow mb-3">The collection</p>
                    <h2 id="cat-heading" class="h-section">Explore collections</h2>
                </div>
                <a href="{{ route('catalog') }}" class="link hidden text-sm sm:inline">View all products</a>
            </div>
            {{-- Staggered columns on desktop give the grid an editorial rhythm without extra markup. --}}
            <ul class="reveal grid grid-cols-2 gap-3 sm:gap-5 {{ $cols }}" role="list">
                @foreach ($categories as $category)
                    @php $img = $category->imageUrl(); @endphp
                    <li @class(['lg:mt-12' => $loop->even && $categories->count() > 2])>
                        <a href="{{ route('catalog.category', $category) }}" class="group relative block aspect-[4/5] overflow-hidden rounded-sm {{ $img ? 'bg-elevated' : 'bg-band text-on-band' }}">
                            @if ($img)
                                <img src="{{ $img }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-[1.04] motion-reduce:transition-none">
                                <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/75 via-black/25 to-transparent p-4 pt-20 text-white sm:p-6 sm:pt-24">
                            @else
                                <div class="absolute inset-x-0 bottom-0 p-4 sm:p-6">
                            @endif
                                <h3 class="font-serif text-2xl leading-tight sm:text-3xl">{{ $category->name }}</h3>
                                @if ($category->description)<p class="mt-1 line-clamp-2 hidden text-sm opacity-85 sm:block">{{ $category->description }}</p>@endif
                                <p class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.14em]">Explore <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1"/></p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Raw leather (products flagged "Raw leather"; always quote-only). Hidden when there are none. --}}
    @include('store.partials.raw-leather-gallery', ['products' => $rawLeather])

    {{-- 4. Manufacturing and private label --}}
    <section class="bg-band text-on-band" aria-labelledby="custom-heading">
        <div class="container-page section-y grid items-start gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-6">
                <span class="brass-rule mb-6"></span>
                <h2 id="custom-heading" class="h-section sm:text-5xl lg:text-6xl">Your Brand. Our Craftsmanship.</h2>
                <p class="mt-6 max-w-lg text-lg leading-relaxed text-on-band/80">Private-label programmes from pattern to packaging. Share a tech pack or reference photos and we will quote to your specification.</p>
                <ul class="mt-8 grid gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                    @foreach (config('store.manufacturing.capabilities') as $item)
                        <li class="flex gap-3"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brass"/>{{ $item }}</li>
                    @endforeach
                </ul>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ route('pages.show', 'custom-manufacturing') }}" class="btn btn-lg border-on-band text-on-band hover:bg-on-band hover:text-band">Private label &amp; manufacturing <x-icon name="arrow-right" class="h-4 w-4"/></a>
                </div>
            </div>
            <ol class="grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:col-span-6 lg:pt-4">
                @foreach (config('store.manufacturing.process') as $i => [$step, $text])
                    <li class="border-t border-on-band/25 pt-5">
                        <span class="font-serif text-4xl text-brass">0{{ $i + 1 }}</span>
                        <p class="mt-2 font-medium">{{ $step }}</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-on-band/70">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 5. Featured products: one section; tabs only when more than one group has products --}}
    @include('store.partials.featured-products', ['tabs' => $productTabs, 'autoplay' => $productsAutoplaySeconds])

    {{-- 6. Why Leamate --}}
    <section class="border-y border-border bg-surface" aria-labelledby="benefits-heading">
        <div class="container-page section-y">
            <div class="mb-10 max-w-2xl lg:mb-14">
                <h2 id="benefits-heading" class="h-section">Why {{ $brand['name'] }}</h2>
                <p class="mt-4 text-muted">Every product shows its minimum order quantity and price breaks. Approved trade accounts see their own group and negotiated pricing automatically.</p>
            </div>
            <div class="reveal grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['scissors', 'Manufacturing capabilities', 'Bags, wallets, belts and accessories made in Sialkot, from existing styles to bespoke patterns.'],
                    ['shield', 'Quality control', 'Goods are checked before dispatch, with samples available on request before volume orders.'],
                    ['box', 'Wholesale supply', 'Transparent volume pricing, with price breaks shown on every product page.'],
                    ['tag', 'Private-label solutions', 'Embossing, custom colours, branded hardware and retail packaging for your brand.'],
                ] as [$icon, $heading, $text])
                    <div class="border-t border-border-strong pt-6">
                        <x-icon :name="$icon" class="mb-4 h-6 w-6 text-brass"/>
                        <h3 class="font-serif text-2xl">{{ $heading }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 7. Company and craftsmanship (copy: Admin → Settings → company introduction, else the brand description) --}}
    <section class="section-y container-page" aria-labelledby="craft-heading">
        <div class="reveal grid items-center gap-10 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-6">
                <p class="eyebrow mb-3">The company</p>
                <h2 id="craft-heading" class="h-section">About {{ $brand['name'] }}</h2>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-muted">{{ setting('company.intro') ?: config('brand.description') }}</p>
                <p class="mt-4 flex items-center gap-2 text-sm text-muted"><x-icon name="pin" class="h-4 w-4 text-brass"/> {{ $brand['address'] }}</p>
                <a href="{{ route('pages.show', 'about') }}" class="btn btn-secondary mt-8">About {{ $brand['name'] }} <x-icon name="arrow-right" class="h-4 w-4"/></a>
            </div>
            <ul class="grid gap-6 border-t border-border-strong pt-8 sm:grid-cols-2 lg:col-span-6 lg:border-l lg:border-t-0 lg:pl-12 lg:pt-0" role="list">
                @foreach ([
                    ['Materials stated plainly', 'Each product page lists the leather type, hardware, finishing and packaging we have verified for that item.'],
                    ['Specifications on request', 'Specification sheets and lead times are available for catalogue products.'],
                    ['Samples before volume', 'Samples are available on request before you commit to a volume order.'],
                    ['Quality checks', 'Goods are checked before dispatch.'],
                ] as [$heading, $text])
                    <li>
                        <h3 class="font-serif text-xl">{{ $heading }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 8. Testimonials: only rendered when genuine content is configured in settings --}}
    @if ($testimonials->isNotEmpty())
        <section class="border-y border-border bg-elevated/60" aria-labelledby="testimonials-heading">
            <div class="container-page section-y">
                <h2 id="testimonials-heading" class="h-section mb-10">What our trade customers say</h2>
                <div class="grid gap-10 md:grid-cols-3">
                    @foreach ($testimonials as $t)
                        <figure class="border-t border-brass pt-6">
                            <blockquote class="font-serif text-2xl leading-snug">“{{ $t['quote'] }}”</blockquote>
                            <figcaption class="mt-5 text-sm text-muted">{{ $t['name'] ?? '' }}@if (! empty($t['company'])), {{ $t['company'] }}@endif</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 9. Final wholesale CTA --}}
    <section class="bg-elevated" aria-labelledby="cta-heading">
        <div class="container-page section-y grid items-center gap-8 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <span class="brass-rule mb-6"></span>
                <h2 id="cta-heading" class="h-section sm:text-5xl lg:text-6xl">Let's Build Something Together.</h2>
                <p class="mt-5 max-w-2xl text-lg leading-relaxed text-muted">Tell us about your products, quantities and customization requirements. Our team will help you explore the right wholesale solution.</p>
            </div>
            <div class="flex flex-col gap-3 lg:col-span-4 lg:items-end">
                <a href="{{ route('quote.basket') }}" class="btn btn-primary btn-lg">Request a Wholesale Quote</a>
                <a href="mailto:{{ $brand['email'] }}" class="link text-sm">or email {{ $brand['email'] }}</a>
            </div>
        </div>
    </section>

    {{-- Newsletter: optional and visually secondary to the quotation CTA --}}
    @if (setting('newsletter.enabled'))
        <section class="border-t border-border" aria-label="Newsletter">
            <div class="container-page flex flex-col items-start justify-between gap-5 py-10 md:flex-row md:items-center">
                <div><h2 class="text-2xl">Trade newsletter</h2><p class="mt-1 text-sm text-muted">New collections, restocks and production slots. No spam.</p></div>
                <div class="w-full md:w-auto md:min-w-96">@include('partials.newsletter-form')</div>
            </div>
        </section>
    @endif
</x-layouts.store>
