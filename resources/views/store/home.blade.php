<x-layouts.store :description="'Wholesale and private-label leather bags, wallets, belts and accessories from a Sialkot manufacturer. Volume pricing and fast quotations for trade buyers.'" :canonical="route('home')">
    @push('head')
        {{-- Organization + WebSite (with catalogue search) structured data. --}}
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => $brand['name'],
                    'url' => url('/'),
                    'logo' => $brand['logo'] ?? asset(str_replace('.png', '@2x.png', config('brand.default_logo'))),
                    'description' => config('brand.description'),
                    'email' => $brand['email'],
                    'telephone' => preg_replace('/[^\d+]/', '', $brand['phone']),
                    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $brand['address'], 'addressCountry' => 'PK'],
                    'sameAs' => array_values(array_filter([setting('social.instagram'), setting('social.linkedin'), setting('social.facebook')])) ?: null,
                ]),
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => $brand['name'],
                    'publisher' => ['@id' => url('/').'#organization'],
                    'potentialAction' => ['@type' => 'SearchAction', 'target' => route('catalog').'?q={search_term_string}', 'query-input' => 'required name=search_term_string'],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endpush

    {{-- Notices from redirects to home (e.g. "You have been signed out.") --}}
    @if (session('status') || session('warning') || session('error'))
        <div class="container-page pt-6"><x-flash/></div>
    @endif

    {{-- 1. Hero (Admin → Homepage slides) --}}
    @include('store.partials.hero-banner', ['slides' => $heroSlides, 'autoplay' => $heroAutoplaySeconds])

    {{-- 2. Ways to work with us: routes each kind of buyer in one glance. --}}
    <section class="container-page pb-4 pt-2" aria-labelledby="services-heading">
        <h2 id="services-heading" class="sr-only">Ways to work with {{ $brand['name'] }}</h2>
        <ul class="reveal card grid grid-cols-2 overflow-hidden lg:grid-cols-4" role="list">
            @foreach ([
                ['box', 'Wholesale supply', 'Catalogue styles with volume price breaks.', route('catalog'), 'Browse products'],
                ['tag', 'Private label', 'Your logo, colours and packaging.', route('pages.show', 'custom-manufacturing'), 'How it works'],
                ['scissors', 'Custom manufacturing', 'Bespoke patterns from your tech pack.', route('quote.basket'), 'Start a request'],
                ['layers', 'Raw leather', 'Hides and skins, quoted by lot.', route('catalog', ['raw' => 1]), 'See leathers'],
            ] as [$icon, $title, $text, $url, $cta])
                <li class="relative border-border p-4 transition-colors hover:bg-bg sm:p-7 [&:nth-child(-n+2)]:border-b [&:nth-child(odd)]:border-r lg:[&:nth-child(-n+2)]:border-b-0 lg:[&:not(:last-child)]:border-r">
                    <span class="stat-icon mb-4 rounded-full bg-clay-soft sm:mb-5"><x-icon :name="$icon" class="h-[18px] w-[18px]"/></span>
                    <h3 class="font-sans text-[15px] font-semibold tracking-normal sm:text-base">{{ $title }}</h3>
                    <p class="mt-1 hidden text-sm text-muted sm:block">{{ $text }}</p>
                    <a href="{{ $url }}" class="link-arrow mt-3 text-[13px] after:absolute after:inset-0 sm:mt-4 sm:text-sm">{{ $cta }} <x-icon name="arrow-right" class="h-4 w-4"/></a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 3. Collections (categories marked "featured" in Admin → Categories). Bento layout; every
         label sits on a solid card so it reads on any photograph. --}}
    @if ($categories->isNotEmpty())
        @php
            $n = $categories->count();
            // Grid spans per position so the bento always closes neatly (4-column grid on desktop).
            $span = fn (int $i) => match (true) {
                $n === 1 => 'col-span-2 lg:col-span-4 lg:row-span-2',
                $n === 2 => 'col-span-2 lg:col-span-2 lg:row-span-2',
                $i === 0 => 'col-span-2 lg:col-span-2 lg:row-span-2',
                $n === 3 => 'col-span-2 lg:col-span-2',
                $n === 4 && $i === 1 => 'col-span-2 lg:col-span-2',
                default => 'col-span-1',
            };
        @endphp
        <section class="section-y container-page" aria-labelledby="cat-heading">
            <div class="mb-10 flex flex-wrap items-end justify-between gap-6 lg:mb-14">
                <div class="max-w-2xl">
                    <p class="eyebrow eyebrow-stitch mb-4">The collections</p>
                    <h2 id="cat-heading" class="h-section">Explore collections</h2>
                    <p class="lead mt-4 !text-base sm:!text-lg">Bags, belts, wallets and small leather goods, made in our Sialkot workshops for retail, corporate and private-label programmes.</p>
                </div>
                <a href="{{ route('catalog') }}" class="btn btn-secondary">View all products <x-icon name="arrow-right" class="h-4 w-4"/></a>
            </div>
            <ul class="reveal grid grid-cols-2 gap-3 sm:gap-4 lg:auto-rows-[17.5rem] lg:grid-cols-4 lg:gap-5" role="list">
                @foreach ($categories as $category)
                    @php $img = $category->imageUrl(); $big = $loop->first && $n > 1; $kids = $category->children; @endphp
                    <li class="{{ $span($loop->index) }}">
                        <a href="{{ route('catalog.category', $category) }}" class="tile h-full {{ $big ? 'min-h-[22rem]' : 'min-h-[15rem] sm:min-h-[17.5rem]' }}">
                            @if ($img)
                                <img src="{{ $img }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover motion-reduce:transition-none">
                            @endif
                            <div class="tile-label">
                                <div class="min-w-0">
                                    <h3 class="font-serif leading-tight {{ $big ? 'text-[1.75rem] sm:text-[2.125rem]' : 'text-xl sm:text-2xl' }}">{{ $category->name }}</h3>
                                    @if ($big && $kids->isNotEmpty())
                                        <p class="mt-1.5 line-clamp-2 text-sm text-muted">{{ $kids->take(4)->pluck('name')->implode(' · ') }}@if ($kids->count() > 4) · +{{ $kids->count() - 4 }} more @endif</p>
                                    @elseif ($kids->isNotEmpty())
                                        <p class="mt-1 hidden text-xs text-muted sm:block">{{ $kids->count() }} {{ \Illuminate\Support\Str::plural('range', $kids->count()) }}</p>
                                    @endif
                                </div>
                                <span class="tile-arrow" aria-hidden="true"><x-icon name="arrow-right" class="h-4 w-4"/></span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 4. Featured products: one section; tabs only when more than one group has products --}}
    @include('store.partials.featured-products', ['tabs' => $productTabs, 'autoplay' => $productsAutoplaySeconds])

    {{-- 5. Raw leather (products flagged "Raw leather"; always quote-only). Hidden when there are none. --}}
    @include('store.partials.raw-leather-gallery', ['products' => $rawLeather])

    {{-- 6. Private label and manufacturing: the dark band, with a stitched process timeline. --}}
    <section class="band grain" aria-labelledby="custom-heading">
        <div class="container-page section-y grid gap-14 lg:grid-cols-12 lg:gap-20">
            <div class="lg:col-span-6">
                <p class="eyebrow eyebrow-stitch mb-5">Private label &amp; manufacturing</p>
                <h2 id="custom-heading" class="h-section">Your brand. <span class="italic text-hide">Our craftsmanship.</span></h2>
                <p class="mt-6 max-w-lg text-lg leading-relaxed text-on-band/75">Private-label programmes from pattern to packaging. Share a tech pack or reference photos and we will quote to your specification.</p>
                <ul class="mt-8 flex flex-wrap gap-2" role="list">
                    @foreach (config('store.manufacturing.capabilities') as $item)
                        <li class="inline-flex items-center gap-2 rounded-full border border-on-band/15 bg-band-2 px-3.5 py-1.5 text-[13px] text-on-band/85"><x-icon name="check" class="h-3.5 w-3.5 text-thread"/>{{ $item }}</li>
                    @endforeach
                </ul>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ route('pages.show', 'custom-manufacturing') }}" class="btn btn-primary btn-lg">Private label &amp; manufacturing <x-icon name="arrow-right" class="h-4 w-4"/></a>
                    <a href="{{ route('quote.basket') }}" class="btn btn-outline-light btn-lg">Send a brief</a>
                </div>
            </div>
            <ol class="relative lg:col-span-6 lg:pt-2">
                @foreach (config('store.manufacturing.process') as $i => [$step, $text])
                    <li class="relative flex gap-6 pb-10 last:pb-0">
                        @unless ($loop->last)<span class="stitch-v absolute bottom-0 left-[1.4375rem] top-14" aria-hidden="true"></span>@endunless
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-[1.5px] border-dashed border-thread/60 font-serif text-lg text-thread tabular-nums">0{{ $i + 1 }}</span>
                        <div class="pt-2.5">
                            <h3 class="font-sans text-base font-semibold tracking-normal text-on-band">{{ $step }}</h3>
                            <p class="mt-1.5 max-w-md text-sm leading-relaxed text-on-band/65">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 7. The company and the standard it trades on (one section: no repeated claims). Copy:
         Admin → Settings → company introduction, else the brand description. --}}
    <section class="section-y container-page" aria-labelledby="craft-heading">
        <div class="reveal grid items-start gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:sticky lg:top-32 lg:col-span-5">
                <p class="eyebrow eyebrow-stitch mb-4">The company</p>
                <h2 id="craft-heading" class="h-section">About {{ $brand['name'] }}</h2>
                <p class="lead mt-6 !text-lg">{{ setting('company.intro') ?: config('brand.description') }}</p>
                <p class="mt-5 flex items-center gap-2 text-sm text-muted"><x-icon name="pin" class="h-4 w-4 text-clay"/> {{ $brand['address'] }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('pages.show', 'about') }}" class="btn btn-secondary">About {{ $brand['name'] }} <x-icon name="arrow-right" class="h-4 w-4"/></a>
                    <a href="{{ route('contact') }}" class="btn btn-ghost">Contact us</a>
                </div>
            </div>
            <div class="card overflow-hidden lg:col-span-7">
                <div class="flex items-center justify-between gap-4 bg-elevated px-6 py-4 sm:px-8">
                    <h3 class="font-sans text-xs font-semibold uppercase tracking-[0.16em] text-muted">The {{ $brand['name'] }} standard</h3>
                    <span class="text-xs text-muted">Trade terms at a glance</span>
                </div>
                <dl class="divide-y divide-border">
                    @foreach ([
                        ['Made in Sialkot', 'Bags, wallets, belts and accessories made in our workshops, from existing styles to bespoke patterns.'],
                        ['Pricing you can see', 'Every product shows its minimum order quantity and price breaks. Approved trade accounts see their own group and negotiated pricing.'],
                        ['Materials stated plainly', 'Each product page lists the leather type, hardware, finishing and packaging we have verified for that item.'],
                        ['Samples before volume', 'Samples are available on request before you commit to a volume order, with specification sheets for catalogue products.'],
                        ['Checked before dispatch', 'Goods are quality-checked before they leave us and shipped on the terms in your quotation.'],
                    ] as $i => [$heading, $text])
                        <div class="grid gap-2 px-6 py-5 sm:grid-cols-[3rem_13rem_1fr] sm:gap-6 sm:px-8">
                            <span class="font-serif text-lg text-clay tabular-nums">0{{ $i + 1 }}</span>
                            <dt class="font-serif text-xl leading-snug">{{ $heading }}</dt>
                            <dd class="text-sm leading-relaxed text-muted">{{ $text }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- 8. Testimonials: only rendered when genuine content is configured in settings --}}
    @if ($testimonials->isNotEmpty())
        <section class="border-t border-border bg-elevated" aria-labelledby="testimonials-heading">
            <div class="container-page section-y">
                <p class="eyebrow eyebrow-stitch mb-4">Trade customers</p>
                <h2 id="testimonials-heading" class="h-section mb-12">What our trade customers say</h2>
                <div class="grid gap-5 md:grid-cols-3">
                    @foreach ($testimonials as $t)
                        <figure @class(['flex flex-col rounded-lg p-7 sm:p-8', 'band md:col-span-2' => $loop->first && $testimonials->count() % 3 !== 0, 'bg-surface border border-border' => ! ($loop->first && $testimonials->count() % 3 !== 0)])>
                            <span class="font-serif text-5xl leading-none text-clay" aria-hidden="true">“</span>
                            <blockquote class="mt-2 flex-1 font-serif text-xl leading-snug sm:text-2xl">{{ $t['quote'] }}</blockquote>
                            <figcaption class="mt-6 text-sm opacity-75">{{ $t['name'] ?? '' }}@if (! empty($t['company'])), {{ $t['company'] }}@endif</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.store>
