<x-layouts.admin title="Dashboard">
    <x-page-header title="Dashboard" eyebrow="Overview"/>
    @if ($pendingAccounts)
        <a href="{{ route('admin.customer-accounts', ['status' => 'pending']) }}" class="mb-6 flex items-center justify-between gap-4 rounded-sm border border-current/20 px-4 py-3 text-sm badge-warning" data-testid="pending-accounts">
            <span><strong>{{ $pendingAccounts }}</strong> new customer {{ \Illuminate\Support\Str::plural('account', $pendingAccounts) }} waiting for approval to see prices.</span>
            <span class="font-medium">Review <x-icon name="arrow-right" class="inline h-4 w-4"/></span>
        </a>
    @endif
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['new', 'New requests', 'pending'], ['review', 'In review / quoted', 'under_review'], ['awaiting', 'Awaiting customer', 'awaiting_response'], ['accepted', 'Accepted — ready to convert', 'accepted']] as [$key, $label, $status])
            <a href="{{ route('admin.quotes.index', ['status' => $status]) }}" class="panel block hover:border-accent">
                <p class="text-sm text-muted">{{ $label }}</p>
                <p class="mt-2 font-serif text-4xl">{{ $counts[$key] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <section class="card overflow-x-auto xl:col-span-2">
            <div class="flex items-center justify-between border-b border-border px-5 py-4"><h2 class="text-2xl">Recent quote requests</h2><a href="{{ route('admin.quotes.index') }}" class="link text-sm">All quotes</a></div>
            <table class="table">
                <thead><tr><th>Reference</th><th>Company</th><th>Status</th><th>Assigned</th><th>Received</th></tr></thead>
                <tbody>
                @forelse ($recent as $q)
                    <tr><td><a class="font-medium hover:text-accent" href="{{ route('admin.quotes.show', $q) }}">{{ $q->reference }}</a></td><td>{{ $q->company_name }}</td><td><x-badge :tone="$q->status->tone()">{{ $q->status->label() }}</x-badge></td><td class="text-muted">{{ $q->assignee?->name ?? '—' }}</td><td class="text-muted">{{ $q->created_at->diffForHumans() }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No quote requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
        <div class="space-y-6">
            @if (! empty($contentGaps))
                <div class="panel text-sm" data-testid="content-gaps">
                    <p class="font-medium">Content still needed on the public site</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($contentGaps as $gap)
                            <li class="flex gap-2"><x-icon name="chevron-right" class="mt-0.5 h-4 w-4 shrink-0 text-brass"/><a href="{{ $gap['url'] }}" class="hover:text-accent hover:underline">{{ $gap['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($pendingWholesale !== null)
                <a href="{{ route('admin.companies.index', ['wholesale' => 'pending']) }}" class="panel block hover:border-accent"><p class="text-sm text-muted">Wholesale applications to review</p><p class="mt-2 font-serif text-4xl">{{ $pendingWholesale }}</p></a>
            @endif
            @if ($imageReview !== null)
                <div class="panel"><p class="text-sm text-muted">Images awaiting review</p><p class="mt-2 font-serif text-4xl">{{ $imageReview }}</p></div>
            @endif
            @if ($products)
                <div class="panel text-sm"><p class="text-muted">Catalogue</p><p class="mt-2">{{ $products['published'] }} published · {{ $products['draft'] }} draft</p><a href="{{ route('admin.products.create') }}" class="btn btn-secondary btn-sm mt-4">New product</a></div>
            @endif
            @if ($orders->isNotEmpty())
                <div class="panel text-sm">
                    <p class="mb-3 text-muted">Latest sales orders</p>
                    @foreach ($orders as $o)<a href="{{ route('admin.orders.show', $o) }}" class="flex justify-between py-1 hover:text-accent"><span>{{ $o->reference }}</span><span>{{ money($o->total_cents) }}</span></a>@endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
