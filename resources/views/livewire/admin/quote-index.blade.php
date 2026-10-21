<div>
    <x-page-header title="Quote requests" eyebrow="Sales"/>
    <div class="mb-6 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
        <div class="md:col-span-3 xl:col-span-2">
            <label for="qs" class="sr-only">Search</label>
            <input id="qs" type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="Reference, company, contact or email">
        </div>
        <x-select-field name="status" model="status" :options="$statuses" placeholder="All statuses" aria-label="Status"/>
        <x-select-field name="country" model="country" :options="$countries" placeholder="All countries" aria-label="Country"/>
        <x-select-field name="assigned" model="assigned" :options="['none' => 'Unassigned'] + $staff->all()" placeholder="Any sales rep" aria-label="Sales representative"/>
        <div class="flex gap-2">
            <label class="sr-only" for="from">From date</label><input id="from" type="date" wire:model.live="from" class="input" title="From">
            <label class="sr-only" for="to">To date</label><input id="to" type="date" wire:model.live="to" class="input" title="To">
        </div>
    </div>
    <div class="card overflow-x-auto" wire:loading.class="opacity-60">
        <table class="table">
            <thead><tr><th>Reference</th><th>Company</th><th>Country</th><th>Lines</th><th>Total</th><th>Status</th><th>Sales rep</th><th>Received</th></tr></thead>
            <tbody>
            @forelse ($quotes as $q)
                <tr wire:key="q-{{ $q->id }}">
                    <td><a href="{{ route('admin.quotes.show', $q) }}" class="font-medium hover:text-accent">{{ $q->reference }}</a>@if ($q->has_unsent_changes)<span class="block text-xs text-warning">Unsent changes</span>@endif</td>
                    <td>{{ $q->company_name }}<span class="block text-xs text-muted">{{ $q->contact_name }}@if ($q->company?->wholesale_status === 'approved') · trade account @endif</span></td>
                    <td>{{ $q->country }}</td>
                    <td>{{ $q->items_count }}</td>
                    <td class="whitespace-nowrap">{{ $q->total_cents ? money($q->total_cents) : '—' }}</td>
                    <td><x-badge :tone="$q->status->tone()">{{ $q->status->label() }}</x-badge></td>
                    <td class="text-muted">{{ $q->assignee?->name ?? 'Unassigned' }}</td>
                    <td class="whitespace-nowrap text-muted">{{ $q->created_at->format('j M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-10 text-center text-muted">No quotes match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $quotes->links() }}</div>
</div>
