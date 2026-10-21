<x-layouts.store title="Custom & private label" description="Private-label and custom leather goods manufacturing: embossing, custom colours, materials and packaging.">
    <div class="container-page py-16">
        <x-page-header title="Custom manufacturing & private label" eyebrow="Your brand, our workshop"/>
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="prose-store lg:col-span-7">
                <p>We produce leather goods to your specification — from logo embossing on an existing style to fully bespoke patterns, materials and packaging.</p>
                <h2 class="mt-8 text-3xl">What we can customise</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach (['Logo embossing or debossing (blind or foil)', 'Custom colours and leather types', 'Branded hardware and zip pulls', 'Linings, stitching and edge finishes', 'Retail packaging and swing tags', 'Sample development before production'] as $item)
                        <li class="flex gap-2"><x-icon name="check" class="mt-1 h-4 w-4 shrink-0 text-accent"/>{{ $item }}</li>
                    @endforeach
                </ul>
                <h2 class="mt-10 text-3xl">How to start</h2>
                <ol class="mt-4 list-decimal space-y-2 pl-5">
                    <li>Add a custom programme (or any catalogue product) to your quote list and describe the customisation you need.</li>
                    <li>On the quote form, attach artwork, reference photos or a tech pack (PDF, JPG, PNG or WebP).</li>
                    <li>We reply with a quotation covering sampling, unit prices by volume and lead times.</li>
                </ol>
            </div>
            <aside class="lg:col-span-5">
                <div class="panel">
                    <h2 class="text-2xl">Request a custom quote</h2>
                    <p class="mt-2 text-sm text-muted">Minimum quantities depend on the method and materials. Final pricing is always confirmed in a formal quotation.</p>
                    <div class="mt-5 flex flex-col gap-3">
                        <a href="{{ route('catalog.category', 'custom') }}" class="btn btn-primary">Browse custom programmes</a>
                        <a href="{{ route('quote.basket') }}" class="btn btn-secondary">Go to the quote form</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.store>
