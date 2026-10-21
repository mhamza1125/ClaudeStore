<x-layouts.store title="About the workshop">
    <div class="container-page max-w-3xl py-16">
        <x-page-header title="About {{ $brand['name'] }}" eyebrow="The company"/>
        <div class="prose-store">
            <p>{{ $brand['name'] }} manufactures and supplies leather bags, wallets, belts and accessories to retailers, distributors, corporate buyers and brands.</p>
            <p class="panel text-sm text-muted">Placeholder copy. Replace this page with verified information about your workshop, its location, materials sourcing, certifications and team. Do not publish claims that have not been confirmed.</p>
            <h2 class="mt-10 text-3xl">How we work with trade buyers</h2>
            <ul class="mt-4 list-disc space-y-2 pl-5">
                <li>Browse the catalogue and add products to your quote list with the quantities you need.</li>
                <li>Submit the request — no payment is taken online and a quote request is not an order.</li>
                <li>Our team prepares a formal quotation with pricing, shipping, tax and terms.</li>
                <li>Accept the quotation through your secure link or your account, and we confirm the sales order.</li>
            </ul>
        </div>
        <div class="mt-10 flex gap-3"><a href="{{ route('catalog') }}" class="btn btn-primary">Explore products</a><a href="{{ route('quote.basket') }}" class="btn btn-secondary">Request a quote</a></div>
    </div>
</x-layouts.store>
