@extends('emails.layout')
@section('content')
    {{-- $doc is the sent version's snapshot (QuotationSent carries the version), never live draft data. --}}
    <p>Dear {{ $doc->customer['contact_name'] }},</p>
    <p>Please find our quotation <strong>{{ $doc->reference }}</strong> (version {{ $doc->version }}) for {{ $doc->customer['company_name'] }}. The PDF is attached.</p>
    @include('emails.partials.items', ['items' => $doc->items, 'currency' => $doc->currency, 'showPrices' => true])
    <p style="font-size:16px;"><strong>Grand total: {{ money($doc->totals['total_cents'], $doc->currency) }}</strong> ({{ $doc->currency }})<br>
        @if ($doc->validUntil)<span style="color:#66533f;">Valid until {{ $doc->validUntil->format('j F Y') }}</span>@endif</p>
    @include('emails.partials.button', ['url' => $link, 'label' => 'Review and respond to quotation'])
    @if ($doc->acceptanceInstructions)<p style="font-size:13px;color:#66533f;">{{ $doc->acceptanceInstructions }}</p>@endif
    <p style="font-size:12px;color:#66533f;">This link is personal to your company. If you did not expect this email, please ignore it.</p>
@endsection
