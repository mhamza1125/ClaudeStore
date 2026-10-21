@extends('emails.layout')
@section('content')
    <p><strong>{{ $company->name }}</strong> has requested wholesale pricing access.</p>
    @if ($company->wholesale_application)<p>{{ $company->wholesale_application }}</p>@endif
    @include('emails.partials.button', ['url' => route('admin.companies.show', $company), 'label' => 'Review application'])
@endsection
