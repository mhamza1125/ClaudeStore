@extends('emails.layout')
@section('content')
    <p>Hello,</p>
    @if ($company->wholesale_status === 'approved')
        <p>Your trade account for <strong>{{ $company->name }}</strong> has been approved. Signed-in members of your company now see your account pricing on product pages and in quotations.</p>
        @include('emails.partials.button', ['url' => route('account.pricing'), 'label' => 'View your pricing'])
    @else
        <p>We were unable to approve wholesale access for <strong>{{ $company->name }}</strong> at this time. You can still request quotations. Please reply to this email if you have any questions.</p>
    @endif
@endsection
