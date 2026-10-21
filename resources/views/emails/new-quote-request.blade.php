@extends('emails.layout')
@section('content')
    <p><strong>New quote request {{ $quote->reference }}</strong></p>
    <p>{{ $quote->company_name }} — {{ $quote->contact_name }} ({{ $quote->email }}, {{ $quote->phone }})<br>
        Country: {{ $quote->country }} · Deliver to: {{ $quote->delivery_destination }}</p>
    @if ($quote->items->isNotEmpty())@include('emails.partials.items', ['quote' => $quote])@endif
    @if ($quote->specifications)<p><strong>Specifications:</strong> {{ $quote->specifications }}</p>@endif
    @if ($quote->message)<p><strong>Message:</strong> {{ $quote->message }}</p>@endif
    @include('emails.partials.button', ['url' => route('admin.quotes.show', $quote), 'label' => 'Open in quote workspace'])
@endsection
