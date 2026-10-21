<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Quotation {{ $quote->reference }}</title>
<style>
    @page { margin: 36px 40px 60px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #2b1e16; line-height: 1.45; }
    h1, .serif { font-family: 'DejaVu Serif', serif; font-weight: normal; }
    h1 { font-size: 22px; margin: 0; }
    .muted { color: #66533f; }
    .brass { color: #7a5a1f; }
    .eyebrow { font-size: 8px; letter-spacing: 1.5px; text-transform: uppercase; color: #7a5a1f; }
    table { width: 100%; border-collapse: collapse; }
    .items th { background: #efe6d7; text-align: left; padding: 6px; font-size: 8px; text-transform: uppercase; letter-spacing: 1px; }
    .items td { padding: 7px 6px; border-bottom: 1px solid #ddd0bc; vertical-align: top; }
    .right { text-align: right; }
    .totals td { padding: 4px 6px; }
    .grand td { border-top: 2px solid #2b1e16; font-size: 13px; padding-top: 8px; }
    .box { border: 1px solid #ddd0bc; background: #fffcf6; padding: 10px 12px; }
    .footer { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8px; color: #66533f; border-top: 1px solid #ddd0bc; padding-top: 6px; }
</style>
</head>
<body>
<div class="footer">{{ setting('brand.name') }} · {{ setting('brand.email') }} · {{ setting('brand.phone') }} · {{ setting('brand.address') }} — Quotation {{ $quote->reference }} v{{ max(1, $quote->version) }}</div>

<table>
    <tr>
        <td style="width:55%;">
            <div class="serif" style="font-size:20px;">{{ setting('brand.name') }}</div>
            <div class="eyebrow">{{ setting('brand.tagline') }}</div>
        </td>
        <td class="right">
            <div class="eyebrow">Quotation</div>
            <h1>{{ $quote->reference }}</h1>
            <div class="muted">Version {{ max(1, $quote->version) }} · Issued {{ ($quote->sent_at ?? now())->format('j M Y') }}</div>
            @if ($quote->valid_until)<div><strong>Valid until {{ $quote->valid_until->format('j M Y') }}</strong></div>@endif
        </td>
    </tr>
</table>

<table style="margin-top:22px;">
    <tr>
        <td style="width:50%;vertical-align:top;" class="box">
            <div class="eyebrow">Prepared for</div>
            <strong>{{ $quote->company_name }}</strong><br>
            {{ $quote->contact_name }}<br>{{ $quote->email }} · {{ $quote->phone }}<br>
            {{ countries()[$quote->country] ?? $quote->country }}
            @if ($quote->company?->vat_number)<br>VAT/Tax ID: {{ $quote->company->vat_number }}@endif
        </td>
        <td style="width:4%;"></td>
        <td style="width:46%;vertical-align:top;" class="box">
            <div class="eyebrow">Delivery</div>
            {{ $quote->delivery_destination }}<br>
            @if ($quote->target_delivery_date)Target date: {{ $quote->target_delivery_date->format('j M Y') }}<br>@endif
            @if ($quote->po_reference)PO reference: {{ $quote->po_reference }}<br>@endif
            Currency: {{ $quote->currency }}
        </td>
    </tr>
</table>

<table class="items" style="margin-top:22px;">
    <thead><tr><th style="width:40%;">Product</th><th>SKU</th><th class="right">Qty</th><th class="right">Unit price</th><th class="right">Discount</th><th class="right">Line total</th></tr></thead>
    <tbody>
    @foreach ($quote->items as $item)
        <tr>
            <td><strong>{{ $item->product_name }}</strong>@if ($item->variant_name)<br><span class="muted">{{ $item->variant_name }}</span>@endif
                @if ($item->customization)<br><span class="muted">Customisation: {{ $item->customization }}</span>@endif</td>
            <td>{{ $item->sku }}</td>
            <td class="right">{{ number_format($item->quantity) }}</td>
            <td class="right">{{ money($item->unit_price_cents, $quote->currency) }}</td>
            <td class="right">{{ $item->discount_bps ? \App\Support\Money::bpsToPercentString($item->discount_bps).'%' : '—' }}</td>
            <td class="right">{{ money($item->line_total_cents, $quote->currency) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table style="margin-top:10px;">
    <tr>
        <td style="width:55%;"></td>
        <td style="width:45%;">
            <table class="totals">
                <tr><td>Subtotal</td><td class="right">{{ money($quote->subtotal_cents, $quote->currency) }}</td></tr>
                @if ($quote->discount_cents)<tr><td>Discount</td><td class="right">−{{ money($quote->discount_cents, $quote->currency) }}</td></tr>@endif
                <tr><td>Shipping</td><td class="right">{{ money($quote->shipping_cents, $quote->currency) }}</td></tr>
                @if ($quote->additional_charges_cents)<tr><td>{{ $quote->additional_charges_label ?: 'Additional charges' }}</td><td class="right">{{ money($quote->additional_charges_cents, $quote->currency) }}</td></tr>@endif
                <tr><td>{{ setting('tax.label', 'Tax') }} ({{ \App\Support\Money::bpsToPercentString($quote->tax_rate_bps) }}%)</td><td class="right">{{ money($quote->tax_cents, $quote->currency) }}</td></tr>
                <tr class="grand"><td><strong>Grand total ({{ $quote->currency }})</strong></td><td class="right"><strong>{{ money($quote->total_cents, $quote->currency) }}</strong></td></tr>
            </table>
        </td>
    </tr>
</table>

<table style="margin-top:24px;">
    <tr>
        <td style="width:50%;vertical-align:top;padding-right:10px;">
            <div class="eyebrow">Payment terms</div>
            <p>{{ $quote->payment_terms ?: setting('quote.payment_terms') }}</p>
            <div class="eyebrow">Delivery terms</div>
            <p>{{ $quote->delivery_terms ?: setting('quote.delivery_terms') }}</p>
        </td>
        <td style="width:50%;vertical-align:top;padding-left:10px;">
            @if ($quote->customer_notes)<div class="eyebrow">Notes</div><p>{{ $quote->customer_notes }}</p>@endif
            <div class="eyebrow">How to accept</div>
            <p>{{ setting('quote.acceptance_instructions') }}</p>
        </td>
    </tr>
</table>
<p class="muted" style="margin-top:16px;">This quotation is not an invoice. Prices are valid until the date shown and are subject to the terms above. No payment is taken online.</p>
</body>
</html>
