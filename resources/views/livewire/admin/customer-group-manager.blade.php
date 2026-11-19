<div>
    <x-page-header title="Customer groups" eyebrow="Pricing"/>
    @if (session('error'))<div class="mb-4 alert badge-danger">{{ session('error') }}</div>@endif
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            <table class="table">
                <thead><tr><th>Group</th><th>Discount on standard prices</th><th>Companies</th><th></th></tr></thead>
                <tbody>
                @foreach ($groups as $g)
                    <tr><td class="font-medium">{{ $g->name }} @if ($g->is_default)<x-badge tone="accent">Default for new accounts</x-badge>@endif<span class="block text-xs font-normal text-muted">{{ $g->description }}</span></td><td>{{ \App\Support\Money::bpsToPercentString($g->discount_bps) }}%</td><td>{{ $g->companies_count }}</td>
                        <td class="text-right whitespace-nowrap"><button type="button" wire:click="edit({{ $g->id }})" class="btn btn-ghost btn-sm">Edit</button><button type="button" wire:click="delete({{ $g->id }})" wire:confirm="Delete {{ $g->name }}?" class="btn btn-ghost btn-sm text-danger">Delete</button></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <form wire:submit="save" class="panel space-y-4">
            <h2 class="text-2xl">{{ $editingId ? 'Edit group' : 'New group' }}</h2>
            <x-field name="form.name" model="form.name" label="Name" required/>
            <x-textarea-field name="form.description" model="form.description" label="Description" rows="2"/>
            <x-field name="form.discount" model="form.discount" label="Volume discount (%)" help="Applied to standard tier/base prices when no group tier exists. Group tiers are set per product."/>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model="form.is_default"> Default group for new registrations</label>
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
