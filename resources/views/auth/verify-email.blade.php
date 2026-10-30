<x-auth-card title="Verify your email">
    @if (session('status') === 'verification-link-sent')
        <p class="mb-4 text-sm text-success" role="status">A new verification link has been sent to {{ auth()->user()->email }}.</p>
    @endif
    <p class="text-sm text-muted">We sent a verification link to <strong class="text-text">{{ auth()->user()->email }}</strong>. Click it to confirm your address{{ app(\App\Pricing\PriceAccess::class)->verificationRequired() ? ' and unlock wholesale prices' : '' }}.</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <button class="btn btn-primary w-full">Resend verification email</button>
    </form>
    <x-slot:below><a href="{{ route('account.dashboard') }}" class="link text-text">Continue to your account</a></x-slot:below>
</x-auth-card>
