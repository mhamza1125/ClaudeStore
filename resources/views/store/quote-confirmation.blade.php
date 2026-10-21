<x-layouts.store title="Quote request received">
    <div class="container-page max-w-2xl py-20 text-center">
        <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full border border-accent text-accent"><x-icon name="check" class="h-7 w-7"/></div>
        <p class="eyebrow mb-2">Thank you</p>
        <h1 class="text-4xl sm:text-5xl">Your quote request has been received</h1>
        <p class="mt-6 text-muted">Your reference is</p>
        <p class="mt-1 font-serif text-3xl tracking-wide" data-testid="quote-reference">{{ $quote->reference }}</p>
        <div class="panel mt-10 text-left text-sm">
            <h2 class="mb-3 font-serif text-2xl">What happens next</h2>
            <ol class="list-decimal space-y-2 pl-5 text-muted">
                <li>We have emailed a confirmation to <span class="text-text">{{ $quote->email }}</span>.</li>
                <li>A trade specialist reviews your {{ $quote->items_count }} {{ \Illuminate\Support\Str::plural('line', $quote->items_count) }} and prepares a formal quotation.</li>
                <li>You will receive the quotation by email with a secure link to review, accept or reject it.</li>
            </ol>
            <p class="mt-4 text-muted">This is a request for quotation, not an order. No payment is taken online.</p>
        </div>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth<a href="{{ route('account.quotes.index') }}" class="btn btn-primary">View my quotes</a>@endauth
            <a href="{{ route('catalog') }}" class="btn btn-secondary">Continue browsing</a>
        </div>
    </div>
</x-layouts.store>
