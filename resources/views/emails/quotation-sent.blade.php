@extends('emails.layout')
@section('content')
    <p>Dear {{ $quote->contact_name }},</p>
    <p>Please find our quotation <strong>{{ $quote->reference }}</strong> (version {{ $quote->version }}) for {{ $quote->company_name }}. The PDF is attached.</p>
    @include('emails.partials.items', ['quote' => $quote, 'showPrices' => true])
    <p style="font-size:16px;"><strong>Grand total: {{ money($quote->total_cents, $quote->currency) }}</strong> ({{ $quote->currency }})<br>
        @if ($quote->valid_until)<span style="color:#66533f;">Valid until {{ $quote->valid_until->format('j F Y') }}</span>@endif</p>
    @include('emails.partials.button', ['url' => $link, 'label' => 'Review and respond to quotation'])
    <p style="font-size:13px;color:#66533f;">{{ setting('quote.acceptance_instructions') }}</p>
    <p style="font-size:12px;color:#66533f;">This link is personal to your company. If you did not expect this email, please ignore it.</p>
@endsection
