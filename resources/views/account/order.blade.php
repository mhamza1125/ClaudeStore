<x-layouts.account :title="$order->reference">
    <x-page-header :title="'Order '.$order->reference" eyebrow="Sales order">
        <x-slot:actions><x-badge :tone="$order->status->tone()" class="text-sm">{{ $order->status->label() }}</x-badge>
            <form method="POST" action="{{ route('account.orders.reorder', $order) }}">@csrf<button class="btn btn-secondary btn-sm">Reorder these products</button></form></x-slot:actions>
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Product</th><th>SKU</th><th class="text-right">Qty</th><th class="text-right">Unit</th><th class="text-right">Total</th></tr></thead>
            <tbody>
            @foreach ($order->items as $i)
                <tr><td>{{ $i->product_name }}@if ($i->variant_name)<span class="block text-xs text-muted">{{ $i->variant_name }}</span>@endif</td><td class="text-muted">{{ $i->sku }}</td><td class="text-right">{{ number_format($i->quantity) }}</td><td class="text-right">{{ money($i->unit_price_cents, $order->currency) }}</td><td class="text-right">{{ money($i->line_total_cents, $order->currency) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6 grid gap-6 md:grid-cols-2 text-sm">
        <div class="panel"><p class="eyebrow mb-1">Payment terms</p><p>{{ $order->payment_terms }}</p><p class="eyebrow mb-1 mt-4">Delivery terms</p><p>{{ $order->delivery_terms }}</p>@if ($order->quote)<p class="mt-4 text-muted">From quotation <a class="link" href="{{ route('account.quotes.show', $order->quote) }}">{{ $order->quote->reference }}</a></p>@endif</div>
        <dl class="panel space-y-1.5">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ money($order->subtotal_cents, $order->currency) }}</dd></div>
            @if ($order->discount_cents)<div class="flex justify-between"><dt>Discount</dt><dd>−{{ money($order->discount_cents, $order->currency) }}</dd></div>@endif
            <div class="flex justify-between"><dt>Shipping</dt><dd>{{ money($order->shipping_cents, $order->currency) }}</dd></div>
            @if ($order->additional_charges_cents)<div class="flex justify-between"><dt>{{ $order->additional_charges_label ?: 'Additional' }}</dt><dd>{{ money($order->additional_charges_cents, $order->currency) }}</dd></div>@endif
            <div class="flex justify-between"><dt>Tax</dt><dd>{{ money($order->tax_cents, $order->currency) }}</dd></div>
            <div class="flex justify-between border-t border-border pt-2 font-serif text-2xl"><dt>Total</dt><dd>{{ money($order->total_cents, $order->currency) }}</dd></div>
        </dl>
    </div>
</x-layouts.account>
