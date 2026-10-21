<x-auth-card title="Sign in">
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <x-field name="email" type="email" label="Email" required autofocus autocomplete="username"/>
        <x-field name="password" type="password" label="Password" required autocomplete="current-password"/>
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember" class="checkbox"> Remember me</label>
            <a href="{{ route('password.request') }}" class="link">Forgot password?</a>
        </div>
        <button class="btn btn-primary w-full">Sign in</button>
    </form>
    <x-slot:below>New to {{ $brand['name'] }}? <a href="{{ route('register') }}" class="link text-text">Open a trade account</a></x-slot:below>
</x-auth-card>
