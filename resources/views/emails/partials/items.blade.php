<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;margin:16px 0;">
    <tr style="background:#efe6d7;"><th align="left" style="padding:8px;">Product</th><th align="right" style="padding:8px;">Qty</th>@if ($showPrices ?? false)<th align="right" style="padding:8px;">Unit</th><th align="right" style="padding:8px;">Total</th>@endif</tr>
    @foreach ($quote->items as $item)
        <tr>
            <td style="padding:8px;border-bottom:1px solid #ddd0bc;">{{ $item->product_name }}@if ($item->variant_name) — {{ $item->variant_name }}@endif<br><span style="color:#66533f;font-size:11px;">{{ $item->sku }}</span></td>
            <td align="right" style="padding:8px;border-bottom:1px solid #ddd0bc;">{{ number_format($item->quantity) }}</td>
            @if ($showPrices ?? false)
                <td align="right" style="padding:8px;border-bottom:1px solid #ddd0bc;">{{ money($item->unit_price_cents, $quote->currency) }}@if ($item->discount_bps)<br><span style="color:#66533f;font-size:11px;">−{{ \App\Support\Money::bpsToPercentString($item->discount_bps) }}%</span>@endif</td>
                <td align="right" style="padding:8px;border-bottom:1px solid #ddd0bc;">{{ money($item->line_total_cents, $quote->currency) }}</td>
            @endif
        </tr>
    @endforeach
</table>
