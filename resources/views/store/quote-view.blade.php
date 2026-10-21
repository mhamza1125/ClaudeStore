@php $component = $layout === 'account' ? 'layouts.account' : 'layouts.store'; @endphp
<x-dynamic-component :component="$component" :title="'Quotation '.$quote->reference">
    <div class="{{ $layout === 'account' ? '' : 'container-page max-w-5xl py-12' }}">
        <x-page-header :title="'Quotation '.$quote->reference" eyebrow="{{ $quote->company_name }}">
            <x-slot:actions>
                <x-badge :tone="$quote->status->tone()" class="text-sm">{{ $quote->status->label() }}</x-badge>
                @if ($quote->version > 0)<a href="{{ $pdfUrl }}" class="btn btn-secondary btn-sm" target="_blank"><x-icon name="download" class="h-4 w-4"/> PDF</a>@endif
            </x-slot:actions>
        </x-page-header>
        <x-flash/>
        @error('response')<div class="mb-6 badge badge-danger w-full justify-start px-4 py-3 text-sm" role="alert">{{ $message }}</div>@enderror

        @if ($quote->version === 0)
            <div class="panel">
                <p class="font-medium">Your request is being reviewed.</p>
                <p class="mt-1 text-sm text-muted">We will email you when the formal quotation is ready. Requested items:</p>
                <ul class="mt-4 divide-y divide-border text-sm">
                    @foreach ($quote->items as $item)
                        <li class="flex justify-between py-2"><span>{{ $item->product_name }}@if ($item->variant_name) — {{ $item->variant_name }}@endif <span class="text-muted">({{ $item->sku }})</span></span><span>× {{ number_format($item->quantity) }}</span></li>
                    @endforeach
                </ul>
            </div>
        @else
            @if ($quote->has_unsent_changes && $quote->status !== \App\Enums\QuoteStatus::Accepted)
                <div class="mb-6 badge badge-warning w-full justify-start px-4 py-3 text-sm">This quotation is being revised. You will receive the updated version by email.</div>
            @endif
            <div class="grid gap-6 md:grid-cols-3">
                <div class="panel text-sm"><p class="eyebrow mb-2">Prepared for</p><p class="font-medium">{{ $quote->company_name }}</p><p class="text-muted">{{ $quote->contact_name }} · {{ $quote->email }}</p></div>
                <div class="panel text-sm"><p class="eyebrow mb-2">Delivery</p><p>{{ $quote->delivery_destination }}</p>@if ($quote->po_reference)<p class="text-muted">PO {{ $quote->po_reference }}</p>@endif</div>
                <div class="panel text-sm"><p class="eyebrow mb-2">Validity</p><p class="font-medium">{{ $quote->valid_until?->format('j F Y') ?? '—' }}</p><p class="text-muted">Version {{ $quote->version }} · Currency {{ $quote->currency }}</p></div>
            </div>

            <div class="card mt-6 overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Product</th><th>SKU</th><th class="text-right">Qty</th><th class="text-right">Unit price</th><th class="text-right">Discount</th><th class="text-right">Line total</th></tr></thead>
                    <tbody>
                    @foreach ($quote->items as $item)
                        <tr>
                            <td><span class="font-medium">{{ $item->product_name }}</span>@if ($item->variant_name)<span class="block text-xs text-muted">{{ $item->variant_name }}</span>@endif @if ($item->customization)<span class="block text-xs text-muted">Customisation: {{ $item->customization }}</span>@endif</td>
                            <td class="text-muted">{{ $item->sku }}</td>
                            <td class="text-right">{{ number_format($item->quantity) }}</td>
                            <td class="text-right">{{ money($item->unit_price_cents, $quote->currency) }}</td>
                            <td class="text-right">{{ $item->discount_bps ? \App\Support\Money::bpsToPercentString($item->discount_bps).'%' : '—' }}</td>
                            <td class="text-right font-medium">{{ money($item->line_total_cents, $quote->currency) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div class="space-y-4 text-sm">
                    <div><p class="eyebrow mb-1">Payment terms</p><p>{{ $quote->payment_terms ?: setting('quote.payment_terms') }}</p></div>
                    <div><p class="eyebrow mb-1">Delivery terms</p><p>{{ $quote->delivery_terms ?: setting('quote.delivery_terms') }}</p></div>
                    @if ($quote->customer_notes)<div><p class="eyebrow mb-1">Notes</p><p class="whitespace-pre-line">{{ $quote->customer_notes }}</p></div>@endif
                </div>
                <dl class="panel space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ money($quote->subtotal_cents, $quote->currency) }}</dd></div>
                    @if ($quote->discount_cents)<div class="flex justify-between"><dt>Discount</dt><dd>−{{ money($quote->discount_cents, $quote->currency) }}</dd></div>@endif
                    <div class="flex justify-between"><dt>Shipping</dt><dd>{{ money($quote->shipping_cents, $quote->currency) }}</dd></div>
                    @if ($quote->additional_charges_cents)<div class="flex justify-between"><dt>{{ $quote->additional_charges_label ?: 'Additional charges' }}</dt><dd>{{ money($quote->additional_charges_cents, $quote->currency) }}</dd></div>@endif
                    <div class="flex justify-between"><dt>{{ setting('tax.label', 'Tax') }} ({{ \App\Support\Money::bpsToPercentString($quote->tax_rate_bps) }}%)</dt><dd>{{ money($quote->tax_cents, $quote->currency) }}</dd></div>
                    <div class="flex justify-between border-t border-border pt-3 font-serif text-2xl"><dt>Grand total</dt><dd data-testid="grand-total">{{ money($quote->total_cents, $quote->currency) }}</dd></div>
                </dl>
            </div>

            @if ($quote->canBeAnsweredByCustomer() && ! $acceptUrl)
                <div class="panel mt-8 text-sm"><p>This quotation is awaiting a response from {{ $quote->contact_name }} or your company account owner.</p></div>
            @elseif ($quote->canBeAnsweredByCustomer())
                <section class="panel mt-8" aria-labelledby="respond-heading">
                    <h2 id="respond-heading" class="text-3xl">Respond to this quotation</h2>
                    <p class="mt-1 text-sm text-muted">{{ setting('quote.acceptance_instructions') }}</p>
                    <div class="mt-6 grid gap-8 md:grid-cols-2">
                        <form method="POST" action="{{ $acceptUrl }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="version" value="{{ $quote->version }}">
                            <x-field name="name" label="Your full name" required autocomplete="name"/>
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="confirm" value="1" class="checkbox mt-0.5" required>
                                <span>I confirm that I am authorised to accept quotation {{ $quote->reference }} (version {{ $quote->version }}) for {{ money($quote->total_cents, $quote->currency) }} on behalf of {{ $quote->company_name }}, subject to the terms above.</span>
                            </label>
                            @error('confirm')<p class="field-error">{{ $message }}</p>@enderror
                            <button class="btn btn-primary">Accept quotation</button>
                        </form>
                        <form method="POST" action="{{ $rejectUrl }}" class="space-y-4" onsubmit="return confirm('Decline this quotation?')">
                            @csrf
                            <input type="hidden" name="version" value="{{ $quote->version }}">
                            <x-textarea-field name="reason" label="Reason for declining (optional)" rows="3"/>
                            <button class="btn btn-danger">Decline quotation</button>
                        </form>
                    </div>
                </section>
            @elseif ($quote->status === \App\Enums\QuoteStatus::AwaitingResponse && $quote->isExpired() || $quote->status === \App\Enums\QuoteStatus::Expired)
                <div class="panel mt-8"><p class="font-medium">This quotation expired on {{ $quote->valid_until?->format('j F Y') }}.</p><p class="mt-1 text-sm text-muted">It must be revalidated before it can be accepted. Contact <a class="link" href="mailto:{{ setting('brand.email') }}">{{ setting('brand.email') }}</a> quoting {{ $quote->reference }}.</p></div>
            @elseif ($quote->status === \App\Enums\QuoteStatus::Accepted || $quote->status === \App\Enums\QuoteStatus::Converted)
                <div class="panel mt-8"><p class="font-medium">Accepted {{ $quote->responded_at?->format('j F Y') }}@if ($quote->accepted_by_name) by {{ $quote->accepted_by_name }}@endif.</p><p class="mt-1 text-sm text-muted">{{ $quote->status === \App\Enums\QuoteStatus::Converted ? 'Your sales order has been created.' : 'We will confirm your sales order shortly.' }}</p></div>
            @endif
        @endif
    </div>
</x-dynamic-component>
