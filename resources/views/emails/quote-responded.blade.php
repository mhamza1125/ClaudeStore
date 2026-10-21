@extends('emails.layout')
@section('content')
    <p>Quote <strong>{{ $quote->reference }}</strong> ({{ $quote->company_name }}) was <strong>{{ strtolower($quote->status->label()) }}</strong> by the customer on {{ $quote->responded_at?->format('j M Y H:i') }}.</p>
    @if ($quote->accepted_by_name)<p>Accepted by: {{ $quote->accepted_by_name }}</p>@endif
    @if ($quote->rejection_reason)<p>Reason: {{ $quote->rejection_reason }}</p>@endif
    <p>Total: {{ money($quote->total_cents, $quote->currency) }}</p>
    @include('emails.partials.button', ['url' => route('admin.quotes.show', $quote), 'label' => 'Open quote'])
@endsection
