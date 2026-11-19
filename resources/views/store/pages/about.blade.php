<x-layouts.store :canonical="$canonical" title="About Leamate">
    <section class="border-b border-border bg-elevated/60">
        <div class="container-page section-y-sm">
            <p class="eyebrow mb-4">The company</p>
            <h1 class="h-hero max-w-4xl">About {{ $brand['name'] }}</h1>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-muted">{{ setting('company.intro') ?: config('brand.description') }}</p>
        </div>
    </section>

    <div class="container-page section-y grid gap-14 lg:grid-cols-12 lg:gap-16">
        <section class="lg:col-span-7" aria-labelledby="work-heading">
            <h2 id="work-heading" class="h-section">How we work with trade buyers</h2>
            <ol class="mt-8 space-y-6">
                @foreach ([
                    'Browse the catalogue and add products to your quote list with the quantities you need.',
                    'Submit the request. No payment is taken online, and a quote request is not an order.',
                    'Our team prepares a formal quotation with pricing, shipping, tax and terms.',
                    'Accept the quotation through your secure link or your account, and we confirm the sales order.',
                ] as $i => $step)
                    <li class="flex gap-5 border-t border-border pt-5">
                        <span class="font-serif text-3xl leading-none text-brass">0{{ $i + 1 }}</span>
                        <p class="text-muted">{{ $step }}</p>
                    </li>
                @endforeach
            </ol>
            @if (setting('company.story'))
                <div class="prose-store mt-12 whitespace-pre-line text-muted">{{ setting('company.story') }}</div>
            @endif
        </section>

        <aside class="space-y-10 lg:col-span-5" aria-label="Company at a glance">
            <div class="panel">
                <h2 class="text-2xl">At a glance</h2>
                <dl class="mt-5 divide-y divide-border text-sm">
                    <div class="grid grid-cols-3 gap-3 py-3"><dt class="text-muted">Products</dt><dd class="col-span-2">Leather bags, wallets, belts, accessories and raw leather</dd></div>
                    <div class="grid grid-cols-3 gap-3 py-3"><dt class="text-muted">Services</dt><dd class="col-span-2">Wholesale supply, private label and custom manufacturing</dd></div>
                    <div class="grid grid-cols-3 gap-3 py-3"><dt class="text-muted">Based in</dt><dd class="col-span-2">{{ $brand['address'] }}</dd></div>
                    <div class="grid grid-cols-3 gap-3 py-3"><dt class="text-muted">Contact</dt><dd class="col-span-2"><a class="link" href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a></dd></div>
                </dl>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('catalog') }}" class="btn btn-primary">Explore products</a>
                <a href="{{ route('pages.show', 'custom-manufacturing') }}" class="btn btn-secondary">Private label &amp; manufacturing</a>
                <a href="{{ route('quote.basket') }}" class="btn btn-secondary">Request a quote</a>
            </div>
        </aside>
    </div>
</x-layouts.store>
