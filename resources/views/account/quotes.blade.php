<x-layouts.account title="My quotes">
    <x-page-header title="Quotes" eyebrow="History">
        <x-slot:actions><a href="{{ route('quote.basket') }}" class="btn btn-primary btn-sm">New quote request</a></x-slot:actions>
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Reference</th><th>Status</th><th>Lines</th><th>Total</th><th>Valid until</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            @forelse ($quotes as $q)
                <tr>
                    <td><a href="{{ route('account.quotes.show', $q) }}" class="font-medium hover:text-accent">{{ $q->reference }}</a></td>
                    <td><x-badge :tone="$q->status->tone()">{{ $q->status->label() }}</x-badge></td>
                    <td>{{ $q->items_count }}</td>
                    <td>{{ $q->version ? money($q->total_cents, $q->currency) : '—' }}</td>
                    <td class="text-muted">{{ $q->valid_until?->format('j M Y') ?? '—' }}</td>
                    <td class="text-muted">{{ $q->created_at->format('j M Y') }}</td>
                    <td class="text-right">
                        @if ($q->items_count)
                            <form method="POST" action="{{ route('account.quotes.reorder', $q) }}">@csrf<button class="btn btn-ghost btn-sm">Reorder</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-muted">You have not requested any quotes yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $quotes->links() }}</div>
</x-layouts.account>
