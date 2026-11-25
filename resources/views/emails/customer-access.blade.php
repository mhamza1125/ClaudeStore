@extends('emails.layout')
@section('content')
    <p>Hello {{ $user->name }},</p>
    @if ($user->isApproved())
        <p>Good news: your trade account has been approved. Sign in to see wholesale prices on every product and request quotations.</p>
        @if (app(\App\Pricing\PriceAccess::class)->verificationRequired() && ! $user->hasVerifiedEmail())
            <p>Please also verify your email address using the link we sent you — prices appear once it is verified.</p>
        @endif
        @include('emails.partials.button', ['url' => route('catalog'), 'label' => 'Browse products'])
    @else
        <p>We are unable to give your account online price access at this time. You can still send us quotation requests, and our team is happy to help. Simply reply to this email with any questions.</p>
    @endif
@endsection
