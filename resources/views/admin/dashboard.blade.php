<x-layouts.admin title="Dashboard">
    <x-page-header :title="'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening')).', '.\Illuminate\Support\Str::before(auth()->user()->name, ' ')" eyebrow="Overview">
        <x-slot:actions>
            @if ($products)<a href="{{ route('admin.products.create') }}" class="btn btn-secondary btn-sm"><x-icon name="plus" class="h-4 w-4"/> New product</a>@endif
            <a href="{{ route('admin.quotes.index') }}" class="btn btn-primary btn-sm">Open quote queue <x-icon name="arrow-right" class="h-4 w-4"/></a>
        </x-slot:actions>
    </x-page-header>
    @if ($pendingAccounts)
        <a href="{{ route('admin.customer-accounts', ['status' => 'pending']) }}" class="alert badge-warning mb-6 items-center justify-between" data-testid="pending-accounts">
            <span class="flex items-center gap-3"><x-icon name="users" class="h-4 w-4 shrink-0"/><span><strong>{{ $pendingAccounts }}</strong> new customer {{ \Illuminate\Support\Str::plural('account', $pendingAccounts) }} waiting for approval to see prices.</span></span>
            <span class="shrink-0 font-semibold">Review <x-icon name="arrow-right" class="inline h-4 w-4"/></span>
        </a>
    @endif

    {{-- Quote pipeline, left to right. --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['new', 'New requests', 'pending', 'mail'], ['review', 'In review / quoted', 'under_review', 'clipboard'], ['awaiting', 'Awaiting customer', 'awaiting_response', 'clock'], ['accepted', 'Accepted — ready to convert', 'accepted', 'badge-check']] as [$key, $label, $status, $icon])
            <a href="{{ route('admin.quotes.index', ['status' => $status]) }}" @class(['stat', 'border-primary/40 ring-1 ring-primary/10' => $key === 'new' && $counts[$key] > 0])>
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm text-muted">{{ $label }}</p>
                    <span @class(['stat-icon', '!bg-accent-soft !text-accent' => $key === 'new' && $counts[$key] > 0])><x-icon :name="$icon" class="h-[17px] w-[17px]"/></span>
                </div>
                <p class="stat-value">{{ $counts[$key] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2" aria-labelledby="recent-heading">
            <div class="flex items-center justify-between border-b border-border px-5 py-4">
                <h2 id="recent-heading" class="font-sans text-base font-semibold tracking-normal">Recent quote requests</h2>
                <a href="{{ route('admin.quotes.index') }}" class="link-arrow">All quotes <x-icon name="arrow-right" class="h-4 w-4"/></a>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Reference</th><th>Company</th><th>Status</th><th>Assigned</th><th>Received</th></tr></thead>
                    <tbody>
                    @forelse ($recent as $q)
                        <tr><td><a class="font-semibold tabular-nums hover:text-accent" href="{{ route('admin.quotes.show', $q) }}">{{ $q->reference }}</a></td><td>{{ $q->company_name }}</td><td><x-badge :tone="$q->status->tone()">{{ $q->status->label() }}</x-badge></td><td class="text-muted">{{ $q->assignee?->name ?? '—' }}</td><td class="whitespace-nowrap text-muted">{{ $q->created_at->diffForHumans() }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-muted">No quote requests yet. New requests from the storefront appear here.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div class="space-y-4">
            @if (! empty($contentGaps))
                <div class="panel text-sm" data-testid="content-gaps">
                    <p class="flex items-center gap-2 font-semibold"><span class="dot bg-warning" aria-hidden="true"></span>Content still needed on the public site</p>
                    <ul class="mt-3 space-y-1">
                        @foreach ($contentGaps as $gap)
                            <li><a href="{{ $gap['url'] }}" class="-mx-2 flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-elevated hover:text-accent">{{ $gap['label'] }}<x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-muted"/></a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                @if ($pendingWholesale !== null)
                    <a href="{{ route('admin.companies.index', ['wholesale' => 'pending']) }}" class="stat">
                        <div class="flex items-start justify-between gap-3"><p class="text-sm text-muted">Wholesale applications to review</p><span class="stat-icon"><x-icon name="users" class="h-[17px] w-[17px]"/></span></div>
                        <p class="stat-value">{{ $pendingWholesale }}</p>
                    </a>
                @endif
                @if ($imageReview !== null)
                    <div class="stat">
                        <div class="flex items-start justify-between gap-3"><p class="text-sm text-muted">Images awaiting review</p><span class="stat-icon"><x-icon name="image" class="h-[17px] w-[17px]"/></span></div>
                        <p class="stat-value">{{ $imageReview }}</p>
                    </div>
                @endif
            </div>
            @if ($products)
                <div class="panel text-sm">
                    <p class="text-muted">Catalogue</p>
                    <p class="mt-2 flex items-baseline gap-4"><span><span class="font-serif text-2xl tabular-nums">{{ $products['published'] }}</span> published</span><span class="text-muted"><span class="font-serif text-2xl tabular-nums text-text">{{ $products['draft'] }}</span> draft</span></p>
                    <a href="{{ route('admin.products.index') }}" class="link-arrow mt-4">Manage products <x-icon name="arrow-right" class="h-4 w-4"/></a>
                </div>
            @endif
            @if ($orders->isNotEmpty())
                <div class="panel text-sm">
                    <p class="mb-3 text-muted">Latest sales orders</p>
                    <div class="divide-y divide-border">
                        @foreach ($orders as $o)<a href="{{ route('admin.orders.show', $o) }}" class="flex justify-between py-2 hover:text-accent"><span class="font-medium tabular-nums">{{ $o->reference }}</span><span class="tabular-nums">{{ money($o->total_cents) }}</span></a>@endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
