<x-layouts.store :canonical="$canonical" title="Custom & private label" description="Private-label and custom leather goods manufacturing in Sialkot: logo embossing, custom colours, materials, branded hardware and retail packaging.">
    {{-- 1. Hero: the dark band, with the brief → delivery promise up front. --}}
    <section class="band grain">
        <div class="container-page pb-16 pt-8 sm:pb-20 sm:pt-10 lg:pb-24">
            <x-breadcrumbs :items="[['Home', route('home')], ['Private label & manufacturing', $canonical]]" class="mb-12 [&_a]:text-on-band/60 [&_a:hover]:text-on-band [&_span]:!text-on-band"/>
            <div class="grid items-end gap-12 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <p class="eyebrow eyebrow-stitch mb-6">Private label &amp; manufacturing</p>
                    <h1 class="h-hero max-w-[15ch]">Your brand. <span class="italic text-hide">Our craftsmanship.</span></h1>
                    <p class="mt-7 max-w-2xl text-lg leading-relaxed text-on-band/75">We produce leather goods to your specification, from logo embossing on an existing style to fully bespoke patterns, materials and packaging.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row lg:col-span-4 lg:flex-col lg:items-stretch">
                    <a href="{{ route('quote.basket') }}" class="btn btn-primary btn-lg">Request a Wholesale Quote <x-icon name="arrow-right" class="h-4 w-4"/></a>
                    <a href="{{ route('catalog.category', 'custom') }}" class="btn btn-outline-light btn-lg">Browse custom programmes</a>
                </div>
            </div>
        </div>
    </section>

    {{-- 2–3. Services and customisation options --}}
    <section class="section-y container-page" aria-labelledby="services-heading">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-5">
                <p class="eyebrow eyebrow-stitch mb-4">Options</p>
                <h2 id="services-heading" class="h-section">What we can customise</h2>
                <p class="lead mt-5 !text-lg">Minimum quantities depend on the method and materials. Final pricing is always confirmed in a formal quotation.</p>
            </div>
            <ul class="grid gap-3 sm:grid-cols-2 lg:col-span-7" role="list">
                @foreach (config('store.manufacturing.capabilities') as $i => $item)
                    <li class="card flex items-start gap-4 p-5">
                        <span class="font-serif text-lg text-clay tabular-nums">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="pt-0.5 font-medium">{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 4. Process (edit in config/store.php → manufacturing.process): a horizontal stitched track. --}}
    <section class="border-y border-border bg-elevated" aria-labelledby="process-heading">
        <div class="container-page section-y">
            <p class="eyebrow eyebrow-stitch mb-4">The process</p>
            <h2 id="process-heading" class="h-section mb-14 max-w-2xl">From brief to delivery</h2>
            <ol class="relative grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                <span class="stitch absolute left-6 right-6 top-6 hidden lg:block" aria-hidden="true"></span>
                @foreach (config('store.manufacturing.process') as $i => [$step, $text])
                    <li class="relative">
                        <span class="relative flex h-12 w-12 items-center justify-center rounded-full bg-band font-serif text-lg text-on-band tabular-nums">0{{ $i + 1 }}</span>
                        <h3 class="mt-6 font-serif text-2xl">{{ $step }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 5–7. Materials, quality and sampling --}}
    <section class="section-y container-page" aria-labelledby="quality-heading">
        <h2 id="quality-heading" class="sr-only">Materials, quality and sampling</h2>
        <div class="grid gap-5 md:grid-cols-3">
            @foreach ([
                ['layers', 'Materials &amp; finishes', 'Leather type, hardware, finishing and packaging are listed on each product page. Ask us for swatches or a specification sheet for the materials you have in mind.'],
                ['scissors', 'Sampling', 'Samples are developed before production so materials, colours and branding are approved first.'],
                ['badge-check', 'Quality control', 'Goods are checked before dispatch.'],
            ] as [$icon, $heading, $text])
                <div class="panel">
                    <span class="stat-icon rounded-full bg-clay-soft"><x-icon :name="$icon" class="h-[18px] w-[18px]"/></span>
                    <h3 class="mt-5 font-serif text-2xl">{!! $heading !!}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 8–9. How to start + enquiry --}}
    <section class="container-page pb-4" aria-labelledby="start-heading">
        <div class="grid items-center gap-10 rounded-xl border border-border bg-surface p-7 sm:p-10 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <h2 id="start-heading" class="h-product">How to start</h2>
                <ol class="mt-6 space-y-4">
                    @foreach ([
                        'Add a custom programme (or any catalogue product) to your quote list and describe the customisation you need.',
                        'On the quote form, attach artwork, reference photos or a tech pack (PDF, JPG, PNG or WebP).',
                        'We reply with a quotation covering sampling, unit prices by volume and lead times.',
                    ] as $i => $step)
                        <li class="flex gap-4"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent">{{ $i + 1 }}</span><span class="text-muted">{{ $step }}</span></li>
                    @endforeach
                </ol>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row lg:col-span-5 lg:flex-col lg:items-end">
                <a href="{{ route('quote.basket') }}" class="btn btn-primary btn-lg">Go to the quote form <x-icon name="arrow-right" class="h-4 w-4"/></a>
                <a href="{{ route('contact') }}" class="btn btn-secondary btn-lg">Talk to our team</a>
            </div>
        </div>
    </section>
</x-layouts.store>
