@php $component = $layout === 'account' ? 'layouts.account' : 'layouts.store'; @endphp
<x-dynamic-component :component="$component" :title="'Quotation '.$quote->reference" :noindex="true">
    <div class="{{ $layout === 'account' ? '' : 'container-page max-w-5xl py-12' }}">
        <x-page-header :title="'Quotation '.$quote->reference" eyebrow="{{ $quote->company_name }}">
            <x-slot:actions>
                <x-badge :tone="$quote->status->tone()" class="text-sm">{{ $quote->status->label() }}</x-badge>
                @if ($quote->version > 0)<a href="{{ $pdfUrl }}" class="btn btn-secondary btn-sm" target="_blank"><x-icon name="download" class="h-4 w-4"/> PDF</a>@endif
            </x-slot:actions>
        </x-page-header>
        <x-flash/>
        @error('response')<div class="alert badge-danger mb-6" role="alert">{{ $message }}</div>@enderror

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
        @elseif (! $doc)
            {{-- A sent quote always has its version snapshot; never fall back to live (possibly draft) figures. --}}
            <div class="panel"><p class="font-medium">This quotation is not available right now.</p><p class="mt-1 text-sm text-muted">Please contact <a class="link" href="mailto:{{ setting('brand.email') }}">{{ setting('brand.email') }}</a> quoting {{ $quote->reference }}.</p></div>
        @else
            {{-- Figures come only from the sent version's snapshot ($doc), never from unsent edits (audit F-02). --}}
            @if ($quote->has_unsent_changes && $quote->status !== \App\Enums\QuoteStatus::Accepted)
                <div class="alert badge-warning mb-6">This quotation is being revised. You will receive the updated version by email.</div>
            @endif
            <div class="grid gap-6 md:grid-cols-3">
                <div class="panel text-sm"><p class="eyebrow mb-2">Prepared for</p><p class="font-medium">{{ $doc->customer['company_name'] }}</p><p class="text-muted">{{ $doc->customer['contact_name'] }} · {{ $doc->customer['email'] }}</p></div>
                <div class="panel text-sm"><p class="eyebrow mb-2">Delivery</p><p>{{ $doc->customer['delivery_destination'] }}</p>@if ($doc->customer['po_reference'])<p class="text-muted">PO {{ $doc->customer['po_reference'] }}</p>@endif</div>
                <div class="panel text-sm"><p class="eyebrow mb-2">Validity</p><p class="font-medium">{{ $doc->validUntil?->format('j F Y') ?? '—' }}</p><p class="text-muted">Version {{ $doc->version }} · Currency {{ $doc->currency }}</p></div>
            </div>

            <div class="card mt-6 overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Product</th><th>SKU</th><th class="text-right">Qty</th><th class="text-right">Unit price</th><th class="text-right">Discount</th><th class="text-right">Line total</th></tr></thead>
                    <tbody>
                    @foreach ($doc->items as $item)
                        <tr>
                            <td><span class="font-medium">{{ $item->product_name }}</span>@if ($item->variant_name)<span class="block text-xs text-muted">{{ $item->variant_name }}</span>@endif @if ($item->customization)<span class="block text-xs text-muted">Customisation: {{ $item->customization }}</span>@endif</td>
                            <td class="text-muted">{{ $item->sku }}</td>
                            <td class="text-right">{{ number_format($item->quantity) }}</td>
                            <td class="text-right">{{ money($item->unit_price_cents, $doc->currency) }}</td>
                            <td class="text-right">{{ $item->discount_bps ? \App\Support\Money::bpsToPercentString($item->discount_bps).'%' : '—' }}</td>
                            <td class="text-right font-medium">{{ money($item->line_total_cents, $doc->currency) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div class="space-y-4 text-sm">
                    @if ($doc->paymentTerms)<div><p class="eyebrow mb-1">Payment terms</p><p>{{ $doc->paymentTerms }}</p></div>@endif
                    @if ($doc->deliveryTerms)<div><p class="eyebrow mb-1">Delivery terms</p><p>{{ $doc->deliveryTerms }}</p></div>@endif
                    @if ($doc->customerNotes)<div><p class="eyebrow mb-1">Notes</p><p class="whitespace-pre-line">{{ $doc->customerNotes }}</p></div>@endif
                </div>
                <dl class="panel space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ money($doc->totals['subtotal_cents'], $doc->currency) }}</dd></div>
                    @if ($doc->totals['discount_cents'])<div class="flex justify-between"><dt>Discount</dt><dd>−{{ money($doc->totals['discount_cents'], $doc->currency) }}</dd></div>@endif
                    <div class="flex justify-between"><dt>Shipping</dt><dd>{{ money($doc->totals['shipping_cents'], $doc->currency) }}</dd></div>
                    @if ($doc->totals['additional_charges_cents'])<div class="flex justify-between"><dt>{{ $doc->totals['additional_charges_label'] ?: 'Additional charges' }}</dt><dd>{{ money($doc->totals['additional_charges_cents'], $doc->currency) }}</dd></div>@endif
                    <div class="flex justify-between"><dt>{{ $doc->taxLabel }} ({{ \App\Support\Money::bpsToPercentString($doc->totals['tax_rate_bps']) }}%)</dt><dd>{{ money($doc->totals['tax_cents'], $doc->currency) }}</dd></div>
                    <div class="flex justify-between border-t border-border pt-3 font-serif text-2xl"><dt>Grand total</dt><dd data-testid="grand-total">{{ money($doc->totals['total_cents'], $doc->currency) }}</dd></div>
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
                            <input type="hidden" name="version" value="{{ $doc->version }}">
                            <x-field name="name" label="Your full name" required autocomplete="name"/>
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="confirm" value="1" class="checkbox mt-0.5" required>
                                <span>I confirm that I am authorised to accept quotation {{ $quote->reference }} (version {{ $doc->version }}) for {{ money($doc->totals['total_cents'], $doc->currency) }} on behalf of {{ $doc->customer['company_name'] }}, subject to the terms above.</span>
                            </label>
                            @error('confirm')<p class="field-error">{{ $message }}</p>@enderror
                            <button class="btn btn-primary">Accept quotation</button>
                        </form>
                        <form method="POST" action="{{ $rejectUrl }}" class="space-y-4" onsubmit="return confirm('Decline this quotation?')">
                            @csrf
                            <input type="hidden" name="version" value="{{ $doc->version }}">
                            <x-textarea-field name="reason" label="Reason for declining (optional)" rows="3"/>
                            <button class="btn btn-danger">Decline quotation</button>
                        </form>
                    </div>
                </section>
            @elseif ($quote->status === \App\Enums\QuoteStatus::AwaitingResponse && $quote->isExpired() || $quote->status === \App\Enums\QuoteStatus::Expired)
                <div class="panel mt-8"><p class="font-medium">This quotation expired on {{ $doc->validUntil?->format('j F Y') }}.</p><p class="mt-1 text-sm text-muted">It must be revalidated before it can be accepted. Contact <a class="link" href="mailto:{{ setting('brand.email') }}">{{ setting('brand.email') }}</a> quoting {{ $quote->reference }}.</p></div>
            @elseif ($quote->status === \App\Enums\QuoteStatus::Accepted || $quote->status === \App\Enums\QuoteStatus::Converted)
                <div class="panel mt-8"><p class="font-medium">Accepted {{ $quote->responded_at?->format('j F Y') }}@if ($quote->accepted_by_name) by {{ $quote->accepted_by_name }}@endif.</p><p class="mt-1 text-sm text-muted">{{ $quote->status === \App\Enums\QuoteStatus::Converted ? 'Your sales order has been created.' : 'We will confirm your sales order shortly.' }}</p></div>
            @endif
        @endif
    </div>
</x-dynamic-component>
