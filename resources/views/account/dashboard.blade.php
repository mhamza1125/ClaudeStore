<x-layouts.account title="My account">
    <x-page-header :title="'Welcome, '.$user->name" eyebrow="Account overview"/>
    @if (! $user->company)
        <div class="panel mb-6"><p class="font-medium">Create your company profile</p><p class="mt-1 text-sm text-muted">Add your company details and addresses, then request access to wholesale pricing.</p><a href="{{ route('account.company') }}" class="btn btn-primary btn-sm mt-4">Set up company</a></div>
    @elseif ($user->company->wholesale_status !== 'approved')
        <div class="panel mb-6"><p class="font-medium">Wholesale pricing: {{ ucfirst($user->company->wholesale_status === 'none' ? 'not requested' : $user->company->wholesale_status) }}</p><p class="mt-1 text-sm text-muted">Approved trade accounts see group and negotiated prices on every product.</p>@if (in_array($user->company->wholesale_status, ['none', 'rejected']))<a href="{{ route('account.company') }}#wholesale" class="btn btn-primary btn-sm mt-4">Request wholesale access</a>@endif</div>
    @endif
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('account.quotes.index') }}" class="panel block hover:border-accent"><p class="text-sm text-muted">Quotes awaiting your response</p><p class="mt-2 font-serif text-4xl">{{ $awaiting }}</p></a>
        <a href="{{ route('account.orders.index') }}" class="panel block hover:border-accent"><p class="text-sm text-muted">Sales orders</p><p class="mt-2 font-serif text-4xl">{{ $orders }}</p></a>
        <a href="{{ route('account.pricing') }}" class="panel block hover:border-accent"><p class="text-sm text-muted">Customer group</p><p class="mt-2 font-serif text-2xl">{{ $user->company?->isWholesaleApproved() ? ($user->company->customerGroup?->name ?? 'Standard') : '—' }}</p></a>
    </div>
    <section class="card mt-8 overflow-x-auto">
        <div class="flex items-center justify-between border-b border-border px-5 py-4"><h2 class="text-2xl">Recent quotes</h2><a href="{{ route('quote.basket') }}" class="btn btn-secondary btn-sm">New quote request</a></div>
        <table class="table">
            <thead><tr><th>Reference</th><th>Status</th><th>Total</th><th>Submitted</th></tr></thead>
            <tbody>
            @forelse ($recent as $q)
                <tr><td><a href="{{ route('account.quotes.show', $q) }}" class="font-medium hover:text-accent">{{ $q->reference }}</a></td><td><x-badge :tone="$q->status->tone()">{{ $q->status->label() }}</x-badge></td><td>{{ $q->version ? money($q->total_cents, $q->currency) : 'Pending' }}</td><td class="text-muted">{{ $q->created_at->format('j M Y') }}</td></tr>
            @empty
                <tr><td colspan="4" class="py-8 text-center text-muted">No quotes yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</x-layouts.account>
