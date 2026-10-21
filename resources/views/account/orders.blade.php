<x-layouts.account title="Orders">
    <x-page-header title="Sales orders" eyebrow="History"/>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Order</th><th>Status</th><th>Total</th><th>Date</th><th></th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr><td><a href="{{ route('account.orders.show', $o) }}" class="font-medium hover:text-accent">{{ $o->reference }}</a></td><td><x-badge :tone="$o->status->tone()">{{ $o->status->label() }}</x-badge></td><td>{{ money($o->total_cents, $o->currency) }}</td><td class="text-muted">{{ $o->created_at->format('j M Y') }}</td>
                    <td class="text-right"><form method="POST" action="{{ route('account.orders.reorder', $o) }}">@csrf<button class="btn btn-ghost btn-sm">Reorder</button></form></td></tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-muted">No orders yet. Accepted quotations become sales orders once confirmed by our team.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-layouts.account>
