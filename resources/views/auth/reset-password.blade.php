<x-auth-card title="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-field name="email" type="email" label="Email" required :value="$request->email"/>
        <x-field name="password" type="password" label="New password" required autocomplete="new-password"/>
        <x-field name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password"/>
        <button class="btn btn-primary w-full">Reset password</button>
    </form>
</x-auth-card>
