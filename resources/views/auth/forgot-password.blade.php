<x-auth-card title="Reset your password">
    <p class="mb-5 text-sm text-muted">Enter your account email and we will send you a password reset link.</p>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <x-field name="email" type="email" label="Email" required autofocus/>
        <button class="btn btn-primary w-full">Email reset link</button>
    </form>
    <x-slot:below><a href="{{ route('login') }}" class="link text-text">Back to sign in</a></x-slot:below>
</x-auth-card>
