<x-layouts.account title="My pricing">
    <x-page-header title="Your account pricing" eyebrow="Private to {{ $company?->name ?? 'your account' }}"/>
    @if (! $approved)
        <div class="panel"><p class="font-medium">Account pricing is available to approved wholesale accounts.</p><p class="mt-1 text-sm text-muted">Until then you see standard list and volume prices. <a href="{{ route('account.company') }}#wholesale" class="link">Request wholesale access</a>.</p></div>
    @else
        <div class="panel mb-6 text-sm"><p>Customer group: <span class="font-medium">{{ $company->customerGroup?->name ?? 'Standard' }}</span>@if ($company->customerGroup?->discount_bps) — {{ \App\Support\Money::bpsToPercentString($company->customerGroup->discount_bps) }}% off standard volume prices @endif</p>
            <p class="mt-1 text-muted">Precedence: your negotiated prices, then your group prices, then standard volume tiers. Prices update automatically on product pages when you are signed in.</p></div>
        <section class="card mb-6 overflow-x-auto">
            <h2 class="border-b border-border px-5 py-4 text-2xl">Negotiated prices</h2>
            <table class="table">
                <thead><tr><th>Product</th><th>Option</th><th>From qty</th><th class="text-right">Unit price</th><th>Valid</th></tr></thead>
                <tbody>
                @forelse ($negotiated as $p)
                    <tr><td><a href="{{ route('products.show', $p->product) }}" class="hover:text-accent">{{ $p->product->name }}</a><span class="block text-xs text-muted">{{ $p->product->sku }}</span></td><td>{{ $p->variant?->name ?? 'All options' }}</td><td>{{ $p->min_qty }}</td><td class="text-right font-medium">{{ money($p->unit_price_cents) }}</td><td class="text-muted">{{ $p->valid_until ? 'until '.$p->valid_until->format('j M Y') : 'Ongoing' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-muted">No negotiated prices.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
        <section class="card overflow-x-auto">
            <h2 class="border-b border-border px-5 py-4 text-2xl">Group price tiers</h2>
            <table class="table">
                <thead><tr><th>Product</th><th>Tiers</th></tr></thead>
                <tbody>
                @forelse ($groupTiers as $tiers)
                    <tr><td><a href="{{ route('products.show', $tiers->first()->product) }}" class="hover:text-accent">{{ $tiers->first()->product->name }}</a></td>
                        <td class="text-sm">@foreach ($tiers as $t)<span class="mr-4 inline-block">{{ $t->min_qty }}+ @if ($t->variant)({{ $t->variant->name }})@endif: <strong>{{ money($t->unit_price_cents) }}</strong></span>@endforeach</td></tr>
                @empty
                    <tr><td colspan="2" class="py-6 text-center text-muted">No group-specific tiers; standard volume pricing applies.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    @endif
</x-layouts.account>
