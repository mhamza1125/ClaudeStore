<x-layouts.account title="Profile & security">
    <x-page-header title="Profile & security" eyebrow="Account"/>
    <div class="grid gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="panel space-y-4">
            @csrf @method('PUT')
            <h2 class="text-2xl">Your details</h2>
            @if (session('status') === 'profile-information-updated')<p class="text-sm text-success">Profile updated.</p>@endif
            @if (session('status') === 'verification-link-sent')<p class="text-sm text-success">A new verification link has been sent to {{ $user->email }}.</p>@endif
            <x-field name="name" label="Name" required :value="$user->name"/>
            <x-field name="email" type="email" label="Email" required :value="$user->email" help="Changing your email address asks you to verify the new one."/>
            <p class="text-sm" data-testid="email-status">
                @if ($user->hasVerifiedEmail())
                    <x-badge tone="success">Email verified</x-badge>
                @else
                    <x-badge tone="warning">Email not verified</x-badge>
                    <button form="resend-verification" class="link ml-2 text-sm">Resend verification email</button>
                @endif
            </p>
            @foreach ($errors->updateProfileInformation->all() as $e)<p class="field-error">{{ $e }}</p>@endforeach
            <button class="btn btn-primary">Save</button>
        </form>
        <form method="POST" action="{{ route('user-password.update') }}" class="panel space-y-4">
            @csrf @method('PUT')
            <h2 class="text-2xl">Change password</h2>
            @if (session('status') === 'password-updated')<p class="text-sm text-success">Password changed.</p>@endif
            <x-field name="current_password" type="password" label="Current password" required autocomplete="current-password"/>
            <x-field name="password" type="password" label="New password" required autocomplete="new-password"/>
            <x-field name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password"/>
            @foreach ($errors->updatePassword->all() as $e)<p class="field-error">{{ $e }}</p>@endforeach
            <button class="btn btn-primary">Update password</button>
        </form>
    </div>
    @unless ($user->hasVerifiedEmail())
        <form id="resend-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">@csrf</form>
    @endunless
</x-layouts.account>
