<x-layouts.store :canonical="$canonical" title="About Leamate" :description="'About '.$brand['name'].': a leather goods manufacturer in Sialkot, Pakistan, supplying bags, wallets, belts and accessories wholesale and as private label.'">
    <section class="relative overflow-hidden border-b border-border">
        <div class="hero-backdrop !w-[38%] max-md:hidden" aria-hidden="true"></div>
        <div class="container-page relative grid gap-10 pb-16 pt-8 sm:pt-10 lg:grid-cols-12 lg:items-end lg:pb-24">
            <div class="lg:col-span-7">
                <x-breadcrumbs :items="[['Home', route('home')], ['About', $canonical]]" class="mb-10"/>
                <p class="eyebrow eyebrow-stitch mb-5">The company</p>
                <h1 class="h-hero max-w-[14ch]">About {{ $brand['name'] }}</h1>
                <p class="lead mt-7 max-w-2xl">{{ setting('company.intro') ?: config('brand.description') }}</p>
            </div>
            <aside class="lg:col-span-5" aria-label="Company at a glance">
                <div class="card overflow-hidden shadow-md">
                    <div class="bg-band px-6 py-4 text-on-band"><h2 class="font-sans text-xs font-semibold uppercase tracking-[0.16em] text-hide">At a glance</h2></div>
                    <dl class="divide-y divide-border text-sm">
                        <div class="grid grid-cols-3 gap-3 px-6 py-4"><dt class="text-muted">Products</dt><dd class="col-span-2">Leather bags, wallets, belts, accessories and raw leather</dd></div>
                        <div class="grid grid-cols-3 gap-3 px-6 py-4"><dt class="text-muted">Services</dt><dd class="col-span-2">Wholesale supply, private label and custom manufacturing</dd></div>
                        <div class="grid grid-cols-3 gap-3 px-6 py-4"><dt class="text-muted">Based in</dt><dd class="col-span-2">{{ $brand['address'] }}</dd></div>
                        <div class="grid grid-cols-3 gap-3 px-6 py-4"><dt class="text-muted">Contact</dt><dd class="col-span-2"><a class="link" href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a></dd></div>
                    </dl>
                </div>
            </aside>
        </div>
    </section>

    <div class="container-page section-y grid gap-14 lg:grid-cols-12 lg:gap-20">
        <section class="lg:col-span-7" aria-labelledby="work-heading">
            <p class="eyebrow eyebrow-stitch mb-4">Working with us</p>
            <h2 id="work-heading" class="h-section">How we work with trade buyers</h2>
            <ol class="mt-10">
                @foreach ([
                    ['Build your quote list', 'Browse the catalogue and add products to your quote list with the quantities you need.'],
                    ['Send the request', 'Submit the request. No payment is taken online, and a quote request is not an order.'],
                    ['Receive a formal quotation', 'Our team prepares a formal quotation with pricing, shipping, tax and terms.'],
                    ['Accept and confirm', 'Accept the quotation through your secure link or your account, and we confirm the sales order.'],
                ] as $i => [$step, $text])
                    <li class="relative flex gap-6 pb-9 last:pb-0">
                        @unless ($loop->last)<span class="stitch-v absolute bottom-0 left-[1.4375rem] top-14" aria-hidden="true"></span>@endunless
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-[1.5px] border-dashed border-clay/60 bg-surface font-serif text-lg text-clay tabular-nums">0{{ $i + 1 }}</span>
                        <div class="pt-2.5">
                            <h3 class="font-sans text-base font-semibold tracking-normal">{{ $step }}</h3>
                            <p class="mt-1.5 text-muted">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            @if (setting('company.story'))
                <div class="prose-store mt-14 whitespace-pre-line border-t border-border pt-10 text-muted">{{ setting('company.story') }}</div>
            @endif
        </section>

        <aside class="lg:col-span-5" aria-label="Next steps">
            <div class="space-y-3 lg:sticky lg:top-32">
                @foreach ([
                    [route('catalog'), 'box', 'Explore products', 'Bags, belts, wallets and accessories with volume pricing.'],
                    [route('pages.show', 'custom-manufacturing'), 'tag', 'Private label & manufacturing', 'Your logo, colours, materials and packaging.'],
                    [route('quote.basket'), 'quote', 'Request a quote', 'Tell us what you need; we reply within one business day.'],
                ] as [$url, $icon, $title, $text])
                    <a href="{{ $url }}" class="card card-hover group flex items-center gap-4 p-5">
                        <span class="stat-icon rounded-full bg-clay-soft"><x-icon :name="$icon" class="h-[18px] w-[18px]"/></span>
                        <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $title }}</span><span class="block text-sm text-muted">{{ $text }}</span></span>
                        <span class="tile-arrow h-9 w-9 group-hover:border-primary group-hover:bg-primary group-hover:text-on-primary" aria-hidden="true"><x-icon name="arrow-right" class="h-4 w-4"/></span>
                    </a>
                @endforeach
            </div>
        </aside>
    </div>
</x-layouts.store>
