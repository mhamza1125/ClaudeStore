@php use App\Enums\QuoteStatus; use App\Support\Money; @endphp
<div>
    <x-page-header :title="$quote->reference" :eyebrow="'Quote workspace · '.$quote->company_name">
        <x-slot:actions>
            <x-badge :tone="$quote->status->tone()" class="text-sm">{{ $quote->status->label() }}</x-badge>
            @if ($quote->version)<x-badge>Sent v{{ $quote->version }}</x-badge>@endif
            <a href="{{ route('admin.quotes.pdf', $quote) }}" target="_blank" class="btn btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4"/> PDF preview</a>
            @if ($quote->status === QuoteStatus::Pending && $canEdit)
                <button type="button" wire:click="startReview" class="btn btn-secondary btn-sm">Start review</button>
            @endif
            @if (in_array($quote->status, [QuoteStatus::Rejected, QuoteStatus::Expired]) && auth()->user()->can('update', $quote))
                <button type="button" wire:click="reopen" class="btn btn-secondary btn-sm">Reopen for revision</button>
            @endif
            @if ($quote->status === QuoteStatus::Accepted && $canConvert)
                <button type="button" wire:click="convert" wire:confirm="Create a sales order from the accepted quotation?" class="btn btn-accent btn-sm">Convert to sales order</button>
            @endif
            @if ($quote->salesOrder)
                <a href="{{ route('admin.orders.show', $quote->salesOrder) }}" class="btn btn-primary btn-sm">Order {{ $quote->salesOrder->reference }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @foreach (['convert', 'send', 'status', 'response', 'assignee'] as $key)
        @error($key)<div class="mb-4 alert badge-danger" role="alert">{{ $message }}</div>@enderror
    @endforeach
    @if (session('status'))<div class="mb-4 alert badge-success" role="status">{{ session('status') }}</div>@endif
    @if ($sentLink)
        <div class="mb-6 panel text-sm" x-data>
            <p class="font-medium">Secure customer link (shown once — it is also in the email):</p>
            <div class="mt-2 flex gap-2"><input readonly value="{{ $sentLink }}" class="input font-mono text-xs" x-ref="link"><button type="button" class="btn btn-secondary btn-sm" @click="navigator.clipboard.writeText($refs.link.value)"><x-icon name="copy" class="h-4 w-4"/> Copy</button></div>
        </div>
    @endif
    @if ($quote->has_unsent_changes)
        <div class="mb-6 alert badge-warning">This quote has changes that the customer has not received. Send a new version so they can respond.</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            {{-- Lines --}}
            <section class="card overflow-x-auto" aria-labelledby="lines-h">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-5 py-4">
                    <h2 id="lines-h" class="text-2xl">Products &amp; pricing</h2>
                    @if ($canEdit)<button type="button" wire:click="applySuggested" class="btn btn-ghost btn-sm" title="Fill empty unit prices using the pricing engine for this customer">Apply catalogue prices to empty lines</button>@endif
                </div>
                <table class="table">
                    <thead><tr><th>Product</th><th class="w-24">Qty</th><th class="w-32">Unit price</th><th class="w-24">Disc. %</th><th class="text-right">Line total</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($quote->items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td>
                                <p class="font-medium">{{ $item->product_name }}</p>
                                <p class="text-xs text-muted">{{ $item->sku }}@if ($item->variant_name) · {{ $item->variant_name }}@endif</p>
                                @if ($item->indicative_unit_price_cents !== null)<p class="text-xs text-muted">Indicative shown to buyer: {{ money($item->indicative_unit_price_cents) }}</p>@endif
                                @if ($item->customization)<p class="mt-1 text-xs"><span class="text-accent">Customisation:</span> {{ $item->customization }}</p>@endif
                                @if ($item->notes)<p class="text-xs"><span class="text-accent">Notes:</span> {{ $item->notes }}</p>@endif
                            </td>
                            @if ($canEdit)
                                <td><label class="sr-only" for="lq{{ $item->id }}">Quantity</label><input id="lq{{ $item->id }}" type="number" min="1" wire:model.live.debounce.500ms="lines.{{ $item->id }}.quantity" class="input py-1.5">@error("lines.{$item->id}.quantity")<p class="field-error">{{ $message }}</p>@enderror</td>
                                <td><label class="sr-only" for="lp{{ $item->id }}">Unit price</label><input id="lp{{ $item->id }}" inputmode="decimal" placeholder="0.00" wire:model.live.debounce.500ms="lines.{{ $item->id }}.unit_price" class="input py-1.5">@error("lines.{$item->id}.unit_price")<p class="field-error">{{ $message }}</p>@enderror</td>
                                <td><label class="sr-only" for="ld{{ $item->id }}">Discount percent</label><input id="ld{{ $item->id }}" inputmode="decimal" placeholder="0" wire:model.live.debounce.500ms="lines.{{ $item->id }}.discount" class="input py-1.5">@error("lines.{$item->id}.discount")<p class="field-error">{{ $message }}</p>@enderror</td>
                            @else
                                <td>{{ number_format($item->quantity) }}</td><td>{{ money($item->unit_price_cents) }}</td><td>{{ Money::bpsToPercentString($item->discount_bps) }}</td>
                            @endif
                            <td class="text-right whitespace-nowrap">{{ money($item->line_total_cents) }}</td>
                            <td>@if ($canEdit)<button type="button" wire:click="removeLine({{ $item->id }})" wire:confirm="Remove this line?" class="text-muted hover:text-danger" aria-label="Remove line"><x-icon name="trash" class="h-4 w-4"/></button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-muted">General inquiry — no catalogue lines. Add products by SKU below.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @if ($canEdit)
                    <form wire:submit="addLine" class="flex flex-wrap items-end gap-3 border-t border-border px-5 py-4">
                        <x-field name="newSku" model="newSku" label="Add product by SKU" placeholder="e.g. AH-WAL-001-BLA" class="flex-1"/>
                        <x-field name="newQty" model="newQty" type="number" label="Qty" min="1" class="w-24"/>
                        <button class="btn btn-secondary">Add line</button>
                    </form>
                @endif
            </section>

            {{-- Terms --}}
            <section class="panel" aria-labelledby="terms-h">
                <h2 id="terms-h" class="mb-5 text-2xl">Charges, validity &amp; terms</h2>
                <fieldset @disabled(! $canEdit) class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <x-field name="terms.discount" model="terms.discount" label="Order discount ({{ $quote->currency }})" inputmode="decimal"/>
                    <x-field name="terms.shipping" model="terms.shipping" label="Shipping ({{ $quote->currency }})" inputmode="decimal" help="Default flat rate from Settings."/>
                    <x-field name="terms.tax_rate" model="terms.tax_rate" label="Tax rate (%)" inputmode="decimal" help="Default from Settings."/>
                    <x-field name="terms.additional" model="terms.additional" label="Additional charges" inputmode="decimal"/>
                    <x-field name="terms.additional_label" model="terms.additional_label" label="Additional charges label" placeholder="e.g. Embossing set-up"/>
                    <x-field name="terms.valid_until" model="terms.valid_until" type="date" label="Valid until" required/>
                    <x-field name="terms.po_reference" model="terms.po_reference" label="Customer PO reference"/>
                    <x-textarea-field name="terms.payment_terms" model="terms.payment_terms" label="Payment terms" rows="3" class="sm:col-span-2 lg:col-span-1"/>
                    <x-textarea-field name="terms.delivery_terms" model="terms.delivery_terms" label="Delivery terms" rows="3"/>
                    <x-textarea-field name="terms.customer_notes" model="terms.customer_notes" label="Notes shown to customer" rows="3" class="sm:col-span-2 lg:col-span-3"/>
                </fieldset>
            </section>
        </div>

        <aside class="space-y-6 xl:col-span-4">
            {{-- Totals + actions --}}
            <section class="panel" aria-labelledby="totals-h">
                <h2 id="totals-h" class="mb-4 text-2xl">Totals</h2>
                @if ($preview)
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ money($preview->subtotalCents) }}</dd></div>
                        <div class="flex justify-between"><dt>Discount</dt><dd>−{{ money($preview->discountCents) }}</dd></div>
                        <div class="flex justify-between"><dt>Shipping</dt><dd>{{ money($preview->shippingCents) }}</dd></div>
                        <div class="flex justify-between"><dt>Additional</dt><dd>{{ money($preview->additionalCents) }}</dd></div>
                        <div class="flex justify-between"><dt>Tax ({{ Money::bpsToPercentString($preview->taxRateBps) }}%)</dt><dd>{{ money($preview->taxCents) }}</dd></div>
                        <div class="flex justify-between border-t border-border pt-2 font-serif text-2xl"><dt>Total</dt><dd data-testid="preview-total">{{ money($preview->totalCents) }}</dd></div>
                    </dl>
                    <p class="mt-2 text-xs text-muted">Live preview of unsaved values. Saved total: {{ money($quote->total_cents) }}.</p>
                @endif
                @if ($canEdit)
                    <div class="mt-5 flex flex-col gap-2">
                        <button type="button" wire:click="save" class="btn btn-secondary" wire:loading.attr="disabled">Save draft</button>
                        <button type="button" wire:click="send" wire:confirm="Save and email this quotation (with PDF and secure link) to {{ $quote->email }}?" class="btn btn-primary" wire:loading.attr="disabled">
                            {{ $quote->version ? 'Save & send revised quotation' : 'Save & send quotation' }}
                        </button>
                    </div>
                @elseif (in_array($quote->status, [QuoteStatus::Accepted, QuoteStatus::Converted]))
                    <p class="mt-4 text-sm text-muted">Accepted prices are locked. Accepted by {{ $quote->accepted_by_name }} on {{ $quote->responded_at?->format('j M Y H:i') }}.</p>
                @endif
                @if ($quote->sent_at)<p class="mt-3 text-xs text-muted">Last sent {{ $quote->sent_at->format('j M Y H:i') }} to {{ $quote->email }}.</p>@endif
            </section>

            {{-- Customer --}}
            <section class="panel text-sm" aria-labelledby="cust-h">
                <h2 id="cust-h" class="mb-3 text-2xl">Customer</h2>
                <p class="font-medium">{{ $quote->company_name }}</p>
                <p>{{ $quote->contact_name }}</p>
                <p><a class="link" href="mailto:{{ $quote->email }}">{{ $quote->email }}</a> · {{ $quote->phone }}</p>
                <p class="text-muted">{{ countries()[$quote->country] ?? $quote->country }} · Deliver to {{ $quote->delivery_destination }}</p>
                @if ($quote->website)<p class="text-muted">{{ $quote->website }}</p>@endif
                <dl class="mt-3 space-y-1 text-muted">
                    @if ($quote->business_type)<div>Business type: <span class="text-text">{{ $quote->business_type }}</span></div>@endif
                    @if ($quote->expected_volume)<div>Expected volume: <span class="text-text">{{ $quote->expected_volume }}</span></div>@endif
                    @if ($quote->target_delivery_date)<div>Target delivery: <span class="text-text">{{ $quote->target_delivery_date->format('j M Y') }}</span></div>@endif
                    <div>Preferred currency: <span class="text-text">{{ $quote->preferred_currency }}</span></div>
                </dl>
                @if ($quote->company)
                    <p class="mt-3">Account: @can('customers.view')<a class="link" href="{{ route('admin.companies.show', $quote->company) }}">{{ $quote->company->name }}</a>@else{{ $quote->company->name }}@endcan
                        <x-badge :tone="$quote->company->wholesale_status === 'approved' ? 'success' : 'neutral'">{{ ucfirst($quote->company->wholesale_status) }}</x-badge>
                        @if ($quote->company->customerGroup)<span class="text-muted">· {{ $quote->company->customerGroup->name }}</span>@endif</p>
                @else
                    <p class="mt-3 text-muted">Guest request (no account).</p>
                @endif
                @if ($quote->specifications)<div class="mt-4"><p class="eyebrow mb-1">Specifications</p><p class="whitespace-pre-line">{{ $quote->specifications }}</p></div>@endif
                @if ($quote->message)<div class="mt-4"><p class="eyebrow mb-1">Message</p><p class="whitespace-pre-line">{{ $quote->message }}</p></div>@endif
                @if ($quote->attachments->isNotEmpty())
                    <div class="mt-4"><p class="eyebrow mb-1">Attachments</p>
                        <ul class="space-y-1">@foreach ($quote->attachments as $a)<li><a class="link" href="{{ route('attachments.show', $a) }}">{{ $a->original_name }}</a> <span class="text-xs text-muted">({{ number_format($a->size / 1024) }} KB)</span></li>@endforeach</ul>
                    </div>
                @endif
            </section>

            {{-- Assignment --}}
            @if ($canAssign)
                <section class="panel text-sm">
                    <h2 class="mb-3 text-2xl">Sales representative</h2>
                    <div class="flex gap-2">
                        <label for="assignee" class="sr-only">Assign to</label>
                        <select id="assignee" wire:model="assignee" class="input"><option value="">Unassigned</option>@foreach ($staff as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                        <button type="button" wire:click="assign" class="btn btn-secondary btn-sm">Assign</button>
                    </div>
                </section>
            @else
                <section class="panel text-sm"><p class="text-muted">Assigned to <span class="text-text">{{ $quote->assignee?->name ?? 'nobody' }}</span></p></section>
            @endif

            {{-- Versions --}}
            @if ($quote->versions->isNotEmpty())
                <section class="panel text-sm">
                    <h2 class="mb-3 text-2xl">Versions sent</h2>
                    <ul class="divide-y divide-border">
                        @foreach ($quote->versions as $v)
                            <li class="flex justify-between py-2"><span>v{{ $v->version }} · {{ $v->created_at->format('j M Y H:i') }}<span class="block text-xs text-muted">by {{ $v->author?->name ?? 'system' }}</span></span><span>{{ money($v->snapshot['totals']['total_cents'] ?? null) }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Notes & activity --}}
            <section class="panel text-sm" aria-labelledby="act-h">
                <h2 id="act-h" class="mb-3 text-2xl">Internal notes &amp; activity</h2>
                <form wire:submit="addNote" class="mb-4 space-y-2">
                    <x-textarea-field name="note" model="note" label="Add an internal note" rows="2"/>
                    <button class="btn btn-secondary btn-sm">Add note</button>
                </form>
                <ol class="space-y-3">
                    @foreach ($activities as $a)
                        <li class="border-l-2 {{ $a->type === 'note' ? 'border-accent' : ($a->type === 'email_failed' ? 'border-danger' : 'border-border') }} pl-3">
                            <p class="whitespace-pre-line">{{ $a->body }}</p>
                            @if ($a->type === 'price_change' && ! empty($a->meta['lines']))
                                <ul class="mt-1 text-xs text-muted">@foreach ($a->meta['lines'] as $c)<li>{{ $c['sku'] }} {{ str_replace('_', ' ', $c['field']) }}: {{ $c['from'] ?? '—' }} → {{ $c['to'] ?? '—' }}</li>@endforeach</ul>
                            @endif
                            <p class="text-xs text-muted">{{ $a->user?->name ?? 'System/customer' }} · {{ $a->created_at->format('j M Y H:i') }} · {{ str_replace('_', ' ', $a->type) }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        </aside>
    </div>
</div>
