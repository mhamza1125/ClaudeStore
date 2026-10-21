<div>
    <x-page-header title="Customers" eyebrow="Companies & trade accounts"/>
    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <div><label for="cs" class="sr-only">Search</label><input id="cs" type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="Company or email"></div>
        <x-select-field name="wholesale" model="wholesale" :options="['pending' => 'Pending approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'none' => 'Not requested']" placeholder="Any wholesale status" aria-label="Wholesale status"/>
        <x-select-field name="group" model="group" :options="$groups" placeholder="Any customer group" aria-label="Customer group"/>
    </div>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Company</th><th>Country</th><th>Wholesale</th><th>Group</th><th>Sales rep</th><th>Users</th><th>Quotes</th></tr></thead>
            <tbody>
            @forelse ($companies as $c)
                <tr><td><a href="{{ route('admin.companies.show', $c) }}" class="font-medium hover:text-accent">{{ $c->name }}</a><span class="block text-xs text-muted">{{ $c->email }}</span></td><td>{{ $c->country }}</td>
                    <td><x-badge :tone="match($c->wholesale_status) { 'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'neutral' }">{{ ucfirst($c->wholesale_status) }}</x-badge></td>
                    <td class="text-muted">{{ $c->customerGroup?->name ?? '—' }}</td><td class="text-muted">{{ $c->salesRep?->name ?? '—' }}</td><td>{{ $c->users_count }}</td><td>{{ $c->quotes_count }}</td></tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-muted">No companies.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $companies->links() }}</div>
</div>
