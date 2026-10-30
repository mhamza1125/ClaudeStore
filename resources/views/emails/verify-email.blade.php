@extends('emails.layout')
@section('content')
    <p>Hello {{ $user->name }},</p>
    <p>Thank you for opening a trade account with {{ setting('brand.name') }}. Please confirm that <strong>{{ $user->email }}</strong> is your email address.</p>
    @include('emails.partials.button', ['url' => $url, 'label' => 'Verify email address'])
    <p style="font-size:13px;color:#66533f;">This link expires in {{ config('auth.verification.expire', 60) }} minutes. You can request a new one from your account at any time. If you did not create an account, no action is needed.</p>
@endsection
