<x-layouts.admin :title="$order->reference">
    <x-page-header :title="'Sales order '.$order->reference" :eyebrow="$order->company_name">
        <x-slot:actions>
            <x-badge :tone="$order->status->tone()" class="text-sm">{{ $order->status->label() }}</x-badge>
            @if ($order->quote)<a href="{{ route('admin.quotes.show', $order->quote) }}" class="btn btn-secondary btn-sm">Quote {{ $order->quote->reference }}</a>@endif
        </x-slot:actions>
    </x-page-header>
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Product</th><th>SKU</th><th class="text-right">Qty</th><th class="text-right">Unit</th><th class="text-right">Disc.</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                    @foreach ($order->items as $i)
                        <tr><td>{{ $i->product_name }}@if ($i->variant_name)<span class="block text-xs text-muted">{{ $i->variant_name }}</span>@endif @if ($i->customization)<span class="block text-xs text-muted">{{ $i->customization }}</span>@endif</td><td class="text-muted">{{ $i->sku }}</td><td class="text-right">{{ number_format($i->quantity) }}</td><td class="text-right">{{ money($i->unit_price_cents, $order->currency) }}</td><td class="text-right">{{ $i->discount_bps ? \App\Support\Money::bpsToPercentString($i->discount_bps).'%' : '—' }}</td><td class="text-right">{{ money($i->line_total_cents, $order->currency) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="grid gap-6 md:grid-cols-2 text-sm">
                <div class="panel"><p class="eyebrow mb-1">Payment terms</p><p>{{ $order->payment_terms ?? '—' }}</p><p class="eyebrow mb-1 mt-4">Delivery terms</p><p>{{ $order->delivery_terms ?? '—' }}</p></div>
                <dl class="panel space-y-1.5">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ money($order->subtotal_cents, $order->currency) }}</dd></div>
                    <div class="flex justify-between"><dt>Discount</dt><dd>−{{ money($order->discount_cents, $order->currency) }}</dd></div>
                    <div class="flex justify-between"><dt>Shipping</dt><dd>{{ money($order->shipping_cents, $order->currency) }}</dd></div>
                    <div class="flex justify-between"><dt>{{ $order->additional_charges_label ?: 'Additional' }}</dt><dd>{{ money($order->additional_charges_cents, $order->currency) }}</dd></div>
                    <div class="flex justify-between"><dt>Tax ({{ \App\Support\Money::bpsToPercentString($order->tax_rate_bps) }}%)</dt><dd>{{ money($order->tax_cents, $order->currency) }}</dd></div>
                    <div class="flex justify-between border-t border-border pt-2 font-serif text-2xl"><dt>Total</dt><dd>{{ money($order->total_cents, $order->currency) }}</dd></div>
                </dl>
            </div>
        </div>
        <aside class="space-y-6">
            <div class="panel text-sm">
                <p class="font-medium">{{ $order->company_name }}</p><p>{{ $order->contact_name }} · {{ $order->email }}</p><p class="text-muted">{{ $order->phone }}</p>
                <p class="mt-2 text-muted">Deliver to {{ $order->delivery_destination }}</p>
                @if ($order->po_reference)<p class="text-muted">PO {{ $order->po_reference }}</p>@endif
                <p class="mt-3 text-xs text-muted">No online payment. Arrange deposit and balance offline per payment terms.</p>
            </div>
            @can('orders.manage')
                <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="panel space-y-4">
                    @csrf @method('PUT')
                    <x-select-field name="status" label="Status" :options="$statuses" :value="$order->status->value"/>
                    <x-textarea-field name="internal_notes" label="Internal notes" :value="$order->internal_notes"/>
                    <button class="btn btn-primary">Update order</button>
                </form>
            @endcan
        </aside>
    </div>
</x-layouts.admin>
