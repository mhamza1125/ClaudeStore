@extends('emails.layout')
@section('content')
    <p>New message from the website contact form. Reply to this email to answer {{ $m->name }} directly.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;margin:16px 0;">
        <tr><td style="padding:4px 16px 4px 0;color:#66533f;">Name</td><td>{{ $m->name }}</td></tr>
        <tr><td style="padding:4px 16px 4px 0;color:#66533f;">Email</td><td>{{ $m->email }}</td></tr>
        @if ($m->phone)<tr><td style="padding:4px 16px 4px 0;color:#66533f;">Phone</td><td>{{ $m->phone }}</td></tr>@endif
        @if ($m->company)<tr><td style="padding:4px 16px 4px 0;color:#66533f;">Company</td><td>{{ $m->company }}</td></tr>@endif
        @if ($m->subject)<tr><td style="padding:4px 16px 4px 0;color:#66533f;">Subject</td><td>{{ $m->subject }}</td></tr>@endif
    </table>
    <p style="white-space:pre-line;border-left:3px solid #ddd0bc;padding-left:12px;">{{ $m->message }}</p>
    @include('emails.partials.button', ['url' => route('admin.messages'), 'label' => 'Open messages'])
@endsection
