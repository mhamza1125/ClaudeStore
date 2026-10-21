@extends('emails.layout')
@section('content')
    <p>Dear {{ $quote->contact_name }},</p>
    <p>Thank you for your request. Your reference is <strong>{{ $quote->reference }}</strong>.</p>
    @if ($quote->items->isNotEmpty())
        <p>You asked us to quote for:</p>
        @include('emails.partials.items', ['quote' => $quote])
    @endif
    <p>A trade specialist will review your request and send you a formal quotation, usually within one business day. <strong>This is a request for quotation, not an order</strong>, and no payment is due.</p>
    <p>Kind regards,<br>{{ setting('brand.name') }}</p>
@endsection
