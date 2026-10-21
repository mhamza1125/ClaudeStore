<x-layouts.admin title="Sales orders">
    <x-page-header title="Sales orders" eyebrow="Fulfilment"/>
    <form method="get" class="mb-6 flex flex-wrap gap-3">
        <label for="oq" class="sr-only">Search</label><input id="oq" name="q" value="{{ request('q') }}" class="input max-w-xs" placeholder="Reference or company">
        <x-select-field name="status" :options="$statuses" placeholder="All statuses" :value="request('status')" aria-label="Status"/>
        <button class="btn btn-secondary">Filter</button>
    </form>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Order</th><th>Quote</th><th>Company</th><th>Total</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr><td><a href="{{ route('admin.orders.show', $o) }}" class="font-medium hover:text-accent">{{ $o->reference }}</a></td><td class="text-muted">{{ $o->quote?->reference }}</td><td>{{ $o->company_name }}</td><td>{{ money($o->total_cents, $o->currency) }}</td><td><x-badge :tone="$o->status->tone()">{{ $o->status->label() }}</x-badge></td><td class="text-muted">{{ $o->created_at->format('j M Y') }}</td></tr>
            @empty
                <tr><td colspan="6" class="py-10 text-center text-muted">No sales orders yet. Accepted quotes can be converted from the quote workspace.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-layouts.admin>
