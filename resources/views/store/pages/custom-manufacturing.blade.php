<x-layouts.store :canonical="$canonical" title="Custom & private label" description="Private-label and custom leather goods manufacturing: embossing, custom colours, materials and packaging.">
    {{-- 1. Hero --}}
    <section class="bg-band text-on-band">
        <div class="container-page section-y grid items-end gap-10 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <p class="eyebrow mb-5 !text-brass">Private label &amp; manufacturing</p>
                <h1 class="h-hero">Your Brand. Our Craftsmanship.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-on-band/80">We produce leather goods to your specification, from logo embossing on an existing style to fully bespoke patterns, materials and packaging.</p>
            </div>
            <div class="flex flex-col gap-3 lg:col-span-4 lg:items-end">
                <a href="{{ route('quote.basket') }}" class="btn btn-lg border-on-band bg-on-band text-band hover:opacity-90">Request a Wholesale Quote</a>
                <a href="{{ route('catalog.category', 'custom') }}" class="btn btn-lg border-on-band text-on-band hover:bg-on-band hover:text-band">Browse custom programmes</a>
            </div>
        </div>
    </section>

    {{-- 2–3. Services and customisation options --}}
    <section class="section-y container-page" aria-labelledby="services-heading">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-5">
                <h2 id="services-heading" class="h-section">What we can customise</h2>
                <p class="mt-5 text-lg leading-relaxed text-muted">Minimum quantities depend on the method and materials. Final pricing is always confirmed in a formal quotation.</p>
            </div>
            <ul class="grid gap-x-10 gap-y-5 sm:grid-cols-2 lg:col-span-7" role="list">
                @foreach (config('store.manufacturing.capabilities') as $item)
                    <li class="flex gap-3 border-t border-border pt-4"><x-icon name="check" class="mt-1 h-4 w-4 shrink-0 text-brass"/><span>{{ $item }}</span></li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 4. Process (edit in config/store.php → manufacturing.process) --}}
    <section class="border-y border-border bg-surface" aria-labelledby="process-heading">
        <div class="container-page section-y">
            <h2 id="process-heading" class="h-section mb-12 max-w-2xl">From brief to delivery</h2>
            <ol class="grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (config('store.manufacturing.process') as $i => [$step, $text])
                    <li class="border-t border-border-strong pt-5">
                        <span class="font-serif text-5xl text-brass">0{{ $i + 1 }}</span>
                        <h3 class="mt-3 font-serif text-2xl">{{ $step }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 5–7. Materials, quality and sampling --}}
    <section class="section-y container-page" aria-labelledby="quality-heading">
        <h2 id="quality-heading" class="sr-only">Materials, quality and sampling</h2>
        <div class="grid gap-10 md:grid-cols-3">
            @foreach ([
                ['Materials &amp; finishes', 'Leather type, hardware, finishing and packaging are listed on each product page. Ask us for swatches or a specification sheet for the materials you have in mind.'],
                ['Sampling', 'Samples are developed before production so materials, colours and branding are approved first.'],
                ['Quality control', 'Goods are checked before dispatch.'],
            ] as [$heading, $text])
                <div class="border-t border-brass pt-6">
                    <h3 class="font-serif text-2xl">{!! $heading !!}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 8–9. How to start + enquiry --}}
    <section class="bg-elevated" aria-labelledby="start-heading">
        <div class="container-page section-y grid items-center gap-10 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <h2 id="start-heading" class="h-section">How to start</h2>
                <ol class="mt-6 list-decimal space-y-3 pl-5 text-muted marker:text-accent">
                    <li>Add a custom programme (or any catalogue product) to your quote list and describe the customisation you need.</li>
                    <li>On the quote form, attach artwork, reference photos or a tech pack (PDF, JPG, PNG or WebP).</li>
                    <li>We reply with a quotation covering sampling, unit prices by volume and lead times.</li>
                </ol>
            </div>
            <div class="flex flex-col gap-3 lg:col-span-5 lg:items-end">
                <a href="{{ route('quote.basket') }}" class="btn btn-primary btn-lg">Go to the quote form</a>
                <a href="{{ route('contact') }}" class="btn btn-secondary btn-lg">Talk to our team</a>
            </div>
        </div>
    </section>
</x-layouts.store>
