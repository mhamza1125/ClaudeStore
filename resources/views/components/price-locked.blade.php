{{-- Explains why prices are hidden for this viewer (B2B access, see App\Pricing\PriceAccess) and what to do next. --}}
@props(['access', 'compact' => false])
@php
    $user = auth()->user();
    $inquiries = app(\App\Pricing\PriceAccess::class)->guestInquiriesAllowed();
@endphp
<div {{ $attributes->merge(['data-testid' => 'price-locked']) }}>
    @switch ($access->reason)
        @case(\App\Pricing\PriceAccessDecision::GUEST)
            <p class="font-serif {{ $compact ? 'text-xl' : 'text-3xl' }}">Wholesale prices</p>
            <p class="mt-1 text-sm text-muted">
                <a href="{{ route('login') }}" class="link">Sign in</a> or <a href="{{ route('register') }}" class="link">create a free trade account</a> to see prices.
                @if ($inquiries) You can still add products to your quote list and request a quotation without an account. @endif
            </p>
            @break
        @case(\App\Pricing\PriceAccessDecision::VERIFY)
            <p class="font-serif {{ $compact ? 'text-xl' : 'text-3xl' }}">Verify your email to see prices</p>
            <p class="mt-1 text-sm text-muted">We sent a verification link to <strong class="text-text">{{ $user?->email }}</strong>. Didn’t get it?</p>
            <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
                @csrf
                <button class="link text-sm">Resend the verification email</button>
            </form>
            @break
        @case(\App\Pricing\PriceAccessDecision::APPROVAL)
            <p class="font-serif {{ $compact ? 'text-xl' : 'text-3xl' }}">Prices after approval</p>
            <p class="mt-1 text-sm text-muted">Your account is waiting for approval by our trade team. We’ll email you as soon as prices are unlocked. Meanwhile you can request a quotation.</p>
            @break
        @default
            <p class="font-serif {{ $compact ? 'text-xl' : 'text-3xl' }}">Price on request</p>
            <p class="mt-1 text-sm text-muted">Prices aren’t available for your account online. Add products to your quote list or <a href="{{ route('pages.show', 'about') }}" class="link">contact us</a> for a quotation.</p>
    @endswitch
</div>
