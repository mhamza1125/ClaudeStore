@extends('emails.layout')
@section('content')
    <p>A new customer has registered{{ $user->approval_status === 'pending' ? ' and is waiting for approval to see prices' : '' }}.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;margin:16px 0;">
        <tr><td style="padding:4px 16px 4px 0;color:#66533f;">Name</td><td>{{ $user->name }}</td></tr>
        <tr><td style="padding:4px 16px 4px 0;color:#66533f;">Email</td><td>{{ $user->email }}</td></tr>
        @if ($user->phone)<tr><td style="padding:4px 16px 4px 0;color:#66533f;">Phone</td><td>{{ $user->phone }}</td></tr>@endif
        @if ($user->company)<tr><td style="padding:4px 16px 4px 0;color:#66533f;">Company</td><td>{{ $user->company->name }}@if ($user->company->country) ({{ $user->company->country }})@endif</td></tr>@endif
    </table>
    @include('emails.partials.button', ['url' => route('admin.customer-accounts', ['search' => $user->email]), 'label' => $user->approval_status === 'pending' ? 'Review and approve' : 'View customer account'])
@endsection
