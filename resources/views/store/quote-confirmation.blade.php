<x-layouts.store title="Quote request received" :noindex="true">
    <div class="container-page max-w-2xl py-16 text-center sm:py-24">
        <div class="stitch-frame mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-full bg-primary text-on-primary shadow-cta" style="--stitch-radius: 40px; --stitch-inset: 5px"><x-icon name="check" class="h-8 w-8"/></div>
        <p class="eyebrow mb-3">Thank you</p>
        <h1 class="h-section">Your quote request has been received</h1>
        <p class="mt-8 text-muted">Your reference is</p>
        <p class="mt-2 inline-block rounded-full border border-border bg-surface px-5 py-2 font-serif text-3xl tracking-wide tabular-nums" data-testid="quote-reference">{{ $quote->reference }}</p>
        <div class="panel mt-10 text-left text-sm">
            <h2 class="mb-5 font-serif text-2xl">What happens next</h2>
            <ol class="space-y-4">
                @foreach ([
                    'We have emailed a confirmation to <span class="font-medium text-text">'.e($quote->email).'</span>.',
                    'A trade specialist reviews your '.e($quote->items_count).' '.e(\Illuminate\Support\Str::plural('line', $quote->items_count)).' and prepares a formal quotation.',
                    'You will receive the quotation by email with a secure link to review, accept or reject it.',
                ] as $i => $step)
                    <li class="flex gap-4"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent">{{ $i + 1 }}</span><span class="pt-1 text-muted">{!! $step !!}</span></li>
                @endforeach
            </ol>
            <p class="mt-6 border-t border-border pt-4 text-muted">This is a request for quotation, not an order. No payment is taken online.</p>
        </div>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth<a href="{{ route('account.quotes.index') }}" class="btn btn-primary">View my quotes</a>@endauth
            <a href="{{ route('catalog') }}" class="btn btn-secondary">Continue browsing</a>
        </div>
    </div>
</x-layouts.store>
