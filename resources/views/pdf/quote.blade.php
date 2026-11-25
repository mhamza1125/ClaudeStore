{{-- Renders a QuotationDocument ($doc): the sent snapshot for customers, live data (labelled draft) for staff. --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Quotation {{ $doc->reference }}</title>
<style>
    @page { margin: 36px 40px 60px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #221710; line-height: 1.45; }
    h1, .serif { font-family: 'DejaVu Serif', serif; font-weight: normal; }
    h1 { font-size: 22px; margin: 0; }
    .muted { color: #6b5d52; }
    .brass { color: #a4552a; }
    .eyebrow { font-size: 8px; letter-spacing: 1.5px; text-transform: uppercase; color: #a4552a; }
    table { width: 100%; border-collapse: collapse; }
    .items th { background: #f2ece2; text-align: left; padding: 6px; font-size: 8px; text-transform: uppercase; letter-spacing: 1px; }
    .items td { padding: 7px 6px; border-bottom: 1px solid #e7dfd3; vertical-align: top; }
    .right { text-align: right; }
    .totals td { padding: 4px 6px; }
    .grand td { border-top: 2px solid #221710; font-size: 13px; padding-top: 8px; }
    .box { border: 1px solid #e7dfd3; background: #ffffff; padding: 10px 12px; }
    .footer { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8px; color: #6b5d52; border-top: 1px solid #e7dfd3; padding-top: 6px; }
    .draft { border: 2px solid #9a3b2a; color: #9a3b2a; padding: 6px 10px; margin-bottom: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
</style>
</head>
<body>
<div class="footer">{{ setting('brand.name') }} · {{ setting('brand.email') }} · {{ setting('brand.phone') }} · {{ setting('brand.address') }} — Quotation {{ $doc->reference }} v{{ $doc->version }}@if ($doc->isDraft) · {{ $doc->draftLabel }}@endif</div>

@if ($doc->isDraft)
    <div class="draft">{{ $doc->draftLabel }} — internal copy, not the quotation sent to the customer</div>
@endif

<table>
    <tr>
        <td style="width:55%;">
            <div class="serif" style="font-size:20px;">{{ setting('brand.name') }}</div>
            <div class="eyebrow">{{ setting('brand.tagline') }}</div>
        </td>
        <td class="right">
            <div class="eyebrow">Quotation</div>
            <h1>{{ $doc->reference }}</h1>
            <div class="muted">Version {{ $doc->version }}@if ($doc->issuedAt) · Issued {{ $doc->issuedAt->format('j M Y') }}@endif</div>
            @if ($doc->validUntil)<div><strong>Valid until {{ $doc->validUntil->format('j M Y') }}</strong></div>@endif
        </td>
    </tr>
</table>

<table style="margin-top:22px;">
    <tr>
        <td style="width:50%;vertical-align:top;" class="box">
            <div class="eyebrow">Prepared for</div>
            <strong>{{ $doc->customer['company_name'] }}</strong><br>
            {{ $doc->customer['contact_name'] }}<br>{{ $doc->customer['email'] }} · {{ $doc->customer['phone'] }}<br>
            {{ countries()[$doc->customer['country']] ?? $doc->customer['country'] }}
            @if ($doc->customer['vat_number'])<br>VAT/Tax ID: {{ $doc->customer['vat_number'] }}@endif
        </td>
        <td style="width:4%;"></td>
        <td style="width:46%;vertical-align:top;" class="box">
            <div class="eyebrow">Delivery</div>
            {{ $doc->customer['delivery_destination'] }}<br>
            @if ($doc->customer['target_delivery_date'])Target date: {{ $doc->customer['target_delivery_date']->format('j M Y') }}<br>@endif
            @if ($doc->customer['po_reference'])PO reference: {{ $doc->customer['po_reference'] }}<br>@endif
            Currency: {{ $doc->currency }}
        </td>
    </tr>
</table>

<table class="items" style="margin-top:22px;">
    <thead><tr><th style="width:34%;">Product</th><th>SKU</th><th class="right">Qty</th><th class="right">Unit price</th><th class="right">Discount</th><th class="right">Line total</th></tr></thead>
    <tbody>
    @foreach ($doc->items as $item)
        <tr>
            <td><strong>{{ $item->product_name }}</strong>@if ($item->variant_name)<br><span class="muted">{{ $item->variant_name }}</span>@endif
                @if ($item->customization)<br><span class="muted">Customisation: {{ $item->customization }}</span>@endif</td>
            <td style="white-space:nowrap;font-size:8.5px;">{{ $item->sku }}</td>
            <td class="right">{{ number_format($item->quantity) }}</td>
            <td class="right">{{ money($item->unit_price_cents, $doc->currency) }}</td>
            <td class="right">{{ $item->discount_bps ? \App\Support\Money::bpsToPercentString($item->discount_bps).'%' : '—' }}</td>
            <td class="right">{{ money($item->line_total_cents, $doc->currency) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table style="margin-top:10px;">
    <tr>
        <td style="width:55%;"></td>
        <td style="width:45%;">
            <table class="totals">
                <tr><td>Subtotal</td><td class="right">{{ money($doc->totals['subtotal_cents'], $doc->currency) }}</td></tr>
                @if ($doc->totals['discount_cents'])<tr><td>Discount</td><td class="right">−{{ money($doc->totals['discount_cents'], $doc->currency) }}</td></tr>@endif
                <tr><td>Shipping</td><td class="right">{{ money($doc->totals['shipping_cents'], $doc->currency) }}</td></tr>
                @if ($doc->totals['additional_charges_cents'])<tr><td>{{ $doc->totals['additional_charges_label'] ?: 'Additional charges' }}</td><td class="right">{{ money($doc->totals['additional_charges_cents'], $doc->currency) }}</td></tr>@endif
                <tr><td>{{ $doc->taxLabel }} ({{ \App\Support\Money::bpsToPercentString($doc->totals['tax_rate_bps']) }}%)</td><td class="right">{{ money($doc->totals['tax_cents'], $doc->currency) }}</td></tr>
                <tr class="grand"><td><strong>Grand total ({{ $doc->currency }})</strong></td><td class="right"><strong>{{ money($doc->totals['total_cents'], $doc->currency) }}</strong></td></tr>
            </table>
        </td>
    </tr>
</table>

<table style="margin-top:24px;">
    <tr>
        <td style="width:50%;vertical-align:top;padding-right:10px;">
            @if ($doc->paymentTerms)<div class="eyebrow">Payment terms</div><p>{{ $doc->paymentTerms }}</p>@endif
            @if ($doc->deliveryTerms)<div class="eyebrow">Delivery terms</div><p>{{ $doc->deliveryTerms }}</p>@endif
        </td>
        <td style="width:50%;vertical-align:top;padding-left:10px;">
            @if ($doc->customerNotes)<div class="eyebrow">Notes</div><p>{{ $doc->customerNotes }}</p>@endif
            @if ($doc->acceptanceInstructions)<div class="eyebrow">How to accept</div><p>{{ $doc->acceptanceInstructions }}</p>@endif
        </td>
    </tr>
</table>
<p class="muted" style="margin-top:16px;">This quotation is not an invoice. Prices are valid until the date shown and are subject to the terms above. No payment is taken online.</p>
</body>
</html>
