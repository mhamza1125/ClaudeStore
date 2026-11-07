<x-layouts.store :description="'Wholesale and private-label leather bags, wallets, belts and accessories. Volume pricing and fast quotations for trade buyers.'">
    {{-- Notices from redirects to home (e.g. "You have been signed out.") --}}
    @if (session('status') || session('warning') || session('error'))
        <div class="container-page pt-6"><x-flash/></div>
    @endif
    {{-- Hero banner (Admin → Homepage slides) --}}
    @include('store.partials.hero-banner', ['slides' => $heroSlides, 'autoplay' => $heroAutoplaySeconds])

    {{-- Trust strip --}}
    <div class="border-b border-border">
        <dl class="container-page grid grid-cols-2 gap-x-6 gap-y-3 py-4 text-sm sm:grid-cols-4">
            @foreach ([['tag', 'Live volume pricing', 'Price breaks on every product'], ['clipboard', 'Quotes in 1 business day', 'Mixed products welcome'], ['scissors', 'Private label', 'Embossing & custom colours'], ['shield', 'Trade accounts', 'Your own pricing, kept private']] as [$icon, $title, $text])
                <div class="flex items-start gap-3">
                    <x-icon :name="$icon" class="mt-0.5 h-5 w-5 shrink-0 text-accent"/>
                    <div><dt class="font-medium">{{ $title }}</dt><dd class="text-xs text-muted">{{ $text }}</dd></div>
                </div>
            @endforeach
        </dl>
    </div>

    @include('store.partials.featured-products', ['products' => $featured, 'autoplay' => $productsAutoplaySeconds])

    {{-- Featured categories --}}
    @if ($categories->isNotEmpty())
        <section class="container-page py-20" aria-labelledby="cat-heading">
            <div class="mb-10 flex items-end justify-between gap-4">
                <div><p class="eyebrow mb-2">The collection</p><h2 id="cat-heading" class="text-4xl">Shop by category</h2></div>
                <a href="{{ route('catalog') }}" class="link hidden text-sm sm:inline">View all products</a>
            </div>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
                @foreach ($categories as $category)
                    <a href="{{ route('catalog.category', $category) }}" class="group block">
                        <div class="overflow-hidden rounded-sm bg-elevated">
                            @if ($category->imageUrl())
                                <img src="{{ $category->imageUrl() }}" alt="" loading="lazy" class="aspect-[4/5] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                            @endif
                        </div>
                        <h3 class="mt-3 font-serif text-xl group-hover:text-accent">{{ $category->name }}</h3>
                        <p class="text-xs text-muted">{{ $category->description }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Raw leather gallery (products flagged "Raw leather"; always quote-only) --}}
    @include('store.partials.raw-leather-gallery', ['products' => $rawLeather])

    {{-- Bestsellers --}}
    @if ($bestsellers->isNotEmpty())
        <section class="border-y border-border bg-surface py-20" aria-labelledby="best-heading">
            <div class="container-page">
                <div class="mb-10"><p class="eyebrow mb-2">Most quoted</p><h2 id="best-heading" class="text-4xl">Bestselling products</h2></div>
                <div class="grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
                    @foreach ($bestsellers as $product)<x-product-card :product="$product"/>@endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Wholesale benefits --}}
    <section class="container-page py-20" aria-labelledby="benefits-heading">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <p class="eyebrow mb-2">Why trade with us</p>
                <h2 id="benefits-heading" class="text-4xl">Built for wholesale and bulk orders</h2>
                <p class="mt-4 text-muted">Every product shows its minimum order quantity and price breaks. Approved trade accounts see their own group and negotiated pricing automatically.</p>
            </div>
            <div class="grid gap-8 sm:grid-cols-2 lg:col-span-8">
                @foreach ([
                    ['tag', 'Transparent volume pricing', 'Price breaks are shown on every product and recalculated instantly as you change quantities.'],
                    ['clipboard', 'Mixed-product quotations', 'Combine bags, wallets and belts in one request. Each line uses its own price rules.'],
                    ['shield', 'Account-specific pricing', 'Distributor and negotiated prices are private to your company account.'],
                    ['globe', 'International delivery', 'Quotations include shipping, tax and delivery terms for your destination.'],
                ] as [$icon, $heading, $text])
                    <div class="border-t border-border pt-5">
                        <x-icon :name="$icon" class="mb-3 h-6 w-6 text-accent"/>
                        <h3 class="font-serif text-2xl">{{ $heading }}</h3>
                        <p class="mt-2 text-sm text-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Custom manufacturing / private label --}}
    <section class="bg-band text-on-band" aria-labelledby="custom-heading">
        <div class="container-page grid items-center gap-12 py-20 lg:grid-cols-2">
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] opacity-80">Custom manufacturing</p>
                <h2 id="custom-heading" class="text-4xl sm:text-5xl">Your brand, our workshop.</h2>
                <p class="mt-5 max-w-lg opacity-85">Private-label programmes from pattern to packaging: logo embossing, custom colours and materials, branded hardware and retail-ready packaging. Share a tech pack or reference photos and we will quote to your specification.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('pages.show', 'custom-manufacturing') }}" class="btn border-on-band text-on-band hover:bg-on-band hover:text-band">How private label works</a>
                    <a href="{{ route('catalog.category', 'custom') }}" class="btn btn-accent">Start a custom request</a>
                </div>
            </div>
            <ol class="grid gap-6 sm:grid-cols-2">
                @foreach (['Brief & references', 'Sampling', 'Approval', 'Production & dispatch'] as $i => $step)
                    <li class="border-t border-on-band/25 pt-4">
                        <span class="font-serif text-3xl opacity-60">0{{ $i + 1 }}</span>
                        <p class="mt-1 font-medium">{{ $step }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- New arrivals --}}
    @if ($newArrivals->isNotEmpty())
        <section class="container-page py-20" aria-labelledby="new-heading">
            <div class="mb-10 flex items-end justify-between gap-4">
                <div><p class="eyebrow mb-2">Just added</p><h2 id="new-heading" class="text-4xl">New arrivals</h2></div>
                <a href="{{ route('catalog', ['sort' => 'newest']) }}" class="link hidden text-sm sm:inline">See everything new</a>
            </div>
            <div class="grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
                @foreach ($newArrivals as $product)<x-product-card :product="$product"/>@endforeach
            </div>
        </section>
    @endif

    {{-- Craftsmanship + company --}}
    <section class="border-y border-border bg-surface" aria-labelledby="craft-heading">
        <div class="container-page grid gap-12 py-20 lg:grid-cols-2">
            <div>
                <p class="eyebrow mb-2">Leather &amp; craftsmanship</p>
                <h2 id="craft-heading" class="text-4xl">Materials stated plainly</h2>
                <p class="mt-4 text-muted">Each product page lists the leather type, hardware, finishing and packaging that we have verified for that item. Where a detail is not confirmed, we leave it out rather than guess — ask us for samples or a specification sheet.</p>
                <ul class="mt-6 space-y-3 text-sm">
                    <li class="flex gap-3"><x-icon name="check" class="h-5 w-5 shrink-0 text-accent"/> Specifications and lead times on every product page</li>
                    <li class="flex gap-3"><x-icon name="check" class="h-5 w-5 shrink-0 text-accent"/> Samples available on request before volume orders</li>
                    <li class="flex gap-3"><x-icon name="check" class="h-5 w-5 shrink-0 text-accent"/> Quality checks before dispatch</li>
                </ul>
            </div>
            <div>
                <p class="eyebrow mb-2">The company</p>
                <h2 class="text-4xl">About {{ $brand['name'] }}</h2>
                <p class="mt-4 text-muted">{{ $brand['name'] }} supplies leather goods to retailers, corporate buyers and brands. This introduction is placeholder copy — replace it with your company story, workshop location and certifications.</p>
                <a href="{{ route('pages.show', 'about') }}" class="btn btn-secondary mt-6">About the workshop</a>
            </div>
        </div>
    </section>

    {{-- Testimonials: only rendered when genuine content is configured in settings --}}
    @if ($testimonials->isNotEmpty())
        <section class="container-page py-20" aria-labelledby="testimonials-heading">
            <h2 id="testimonials-heading" class="mb-10 text-4xl">What our trade customers say</h2>
            <div class="grid gap-8 md:grid-cols-3">
                @foreach ($testimonials as $t)
                    <figure class="border-t border-border pt-5">
                        <blockquote class="font-serif text-xl leading-snug">“{{ $t['quote'] }}”</blockquote>
                        <figcaption class="mt-4 text-sm text-muted">{{ $t['name'] ?? '' }}@if (! empty($t['company'])), {{ $t['company'] }}@endif</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Volume inquiry CTA --}}
    <section class="container-page py-20">
        <div class="grid items-center gap-8 rounded-sm border border-border-strong bg-surface p-8 sm:p-12 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <p class="eyebrow mb-2">Large-volume buyers</p>
                <h2 class="text-4xl">Planning an order of 1,000+ units?</h2>
                <p class="mt-3 text-muted">Send us your product mix, target delivery date and destination. A trade specialist will prepare a formal quotation with your pricing, shipping and terms.</p>
            </div>
            <div class="flex flex-col gap-3 lg:items-end">
                <a href="{{ route('quote.basket') }}" class="btn btn-primary btn-lg">Request a Wholesale Quote</a>
                <a href="mailto:{{ $brand['email'] }}" class="link text-sm">or email {{ $brand['email'] }}</a>
            </div>
        </div>
    </section>

    @if (setting('newsletter.enabled'))
        <section class="border-t border-border bg-elevated/50">
            <div class="container-page flex flex-col items-start justify-between gap-6 py-14 md:flex-row md:items-center">
                <div><h2 class="text-3xl">Trade newsletter</h2><p class="mt-1 text-sm text-muted">New collections, restocks and production slots. No spam.</p></div>
                <div class="w-full md:w-auto">@include('partials.newsletter-form')</div>
            </div>
        </section>
    @endif
</x-layouts.store>
