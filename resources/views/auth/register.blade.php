<x-auth-card title="Open a trade account">
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <x-field name="name" label="Your name" required autocomplete="name"/>
        <x-field name="email" type="email" label="Business email" required autocomplete="email"/>
        <x-field name="company_name" label="Company name" help="Creates your company profile. You can request wholesale pricing from your account." autocomplete="organization"/>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="phone" label="Phone (with country code)" autocomplete="tel"/>
            <x-select-field name="country" label="Country" :options="countries()" placeholder="Select…"/>
        </div>
        <x-field name="password" type="password" label="Password" required autocomplete="new-password"/>
        <x-field name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password"/>
        <button class="btn btn-primary w-full">Create account</button>
        <p class="text-xs text-muted">Wholesale pricing is available once your account is approved by our trade team.</p>
    </form>
    <x-slot:below>Already registered? <a href="{{ route('login') }}" class="link text-text">Sign in</a></x-slot:below>
</x-auth-card>
