<div>
    <x-page-header :title="$company->name" eyebrow="Customer account">
        <x-slot:actions>
            <x-badge :tone="match($company->wholesale_status) { 'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'neutral' }" class="text-sm">Wholesale: {{ ucfirst($company->wholesale_status) }}</x-badge>
            @if ($canManage)
                @if ($company->wholesale_status !== 'approved')<button type="button" wire:click="setWholesale('approved')" class="btn btn-accent btn-sm">Approve wholesale</button>@endif
                @if ($company->wholesale_status !== 'rejected')<button type="button" wire:click="setWholesale('rejected')" wire:confirm="Reject or revoke wholesale access?" class="btn btn-danger btn-sm">{{ $company->wholesale_status === 'approved' ? 'Revoke' : 'Reject' }}</button>@endif
            @endif
        </x-slot:actions>
    </x-page-header>
    @if (session('status'))<div class="mb-4 badge badge-success w-full justify-start px-4 py-3 text-sm">{{ session('status') }}</div>@endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="panel grid gap-4 text-sm sm:grid-cols-2">
                <div><p class="eyebrow mb-1">Contact</p><p>{{ $company->email }}</p><p>{{ $company->phone }}</p><p>{{ $company->website }}</p><p class="text-muted">{{ countries()[$company->country] ?? $company->country }} · {{ $company->business_type }}</p>@if ($company->vat_number)<p class="text-muted">VAT {{ $company->vat_number }}</p>@endif</div>
                <div><p class="eyebrow mb-1">Wholesale application</p><p class="whitespace-pre-line">{{ $company->wholesale_application ?: '—' }}</p>@if ($company->wholesale_requested_at)<p class="text-xs text-muted">Requested {{ $company->wholesale_requested_at->format('j M Y') }}</p>@endif</div>
                @foreach ($company->addresses as $a)<div><p class="eyebrow mb-1">{{ ucfirst($a->type) }} address</p><p>{{ $a->singleLine() }}</p></div>@endforeach
            </section>

            <section class="card overflow-x-auto">
                <h2 class="border-b border-border px-5 py-4 text-2xl">Negotiated prices</h2>
                <p class="px-5 pt-3 text-xs text-muted">Highest priority in the pricing engine. Applied only while the company's wholesale status is approved.</p>
                <table class="table">
                    <thead><tr><th>Product</th><th>Option</th><th>From qty</th><th>Unit price</th><th>Validity</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($prices as $p)
                        <tr><td>{{ $p->product->name }}<span class="block text-xs text-muted">{{ $p->product->sku }}</span></td><td>{{ $p->variant?->name ?? 'All' }}</td><td>{{ $p->min_qty }}</td><td class="font-medium">{{ money($p->unit_price_cents) }}</td>
                            <td class="text-xs text-muted">{{ $p->valid_from?->format('j M Y') ?? 'now' }} → {{ $p->valid_until?->format('j M Y') ?? 'ongoing' }}@if ($p->notes)<span class="block">{{ $p->notes }}</span>@endif</td>
                            <td>@if ($canManage)<button type="button" wire:click="deletePrice({{ $p->id }})" wire:confirm="Remove this negotiated price?" class="text-muted hover:text-danger" aria-label="Remove"><x-icon name="trash" class="h-4 w-4"/></button>@endif</td></tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-muted">None.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @if ($canManage)
                    <form wire:submit="addPrice" class="grid gap-3 border-t border-border p-5 sm:grid-cols-6">
                        <x-field name="price.sku" model="price.sku" label="Product or variant SKU" required class="sm:col-span-2"/>
                        <x-field name="price.min_qty" model="price.min_qty" type="number" label="From qty" min="1"/>
                        <x-field name="price.unit_price" model="price.unit_price" label="Unit price" inputmode="decimal" required/>
                        <x-field name="price.valid_from" model="price.valid_from" type="date" label="From"/>
                        <x-field name="price.valid_until" model="price.valid_until" type="date" label="Until"/>
                        <x-field name="price.notes" model="price.notes" label="Notes" class="sm:col-span-5"/>
                        <div class="flex items-end"><button class="btn btn-secondary w-full">Add price</button></div>
                    </form>
                @endif
            </section>

            <section class="card overflow-x-auto">
                <h2 class="border-b border-border px-5 py-4 text-2xl">Recent quotes</h2>
                <table class="table"><tbody>
                @forelse ($quotes as $q)
                    <tr><td><a href="{{ route('admin.quotes.show', $q) }}" class="hover:text-accent">{{ $q->reference }}</a></td><td><x-badge :tone="$q->status->tone()">{{ $q->status->label() }}</x-badge></td><td>{{ $q->total_cents ? money($q->total_cents) : '—' }}</td><td class="text-muted">{{ $q->created_at->format('j M Y') }}</td></tr>
                @empty
                    <tr><td class="py-6 text-center text-muted">No quotes.</td></tr>
                @endforelse
                </tbody></table>
            </section>
        </div>

        <aside class="space-y-6">
            <form wire:submit="saveAccount" class="panel space-y-4">
                <h2 class="text-2xl">Account settings</h2>
                <fieldset @disabled(! $canManage) class="space-y-4">
                    <x-select-field name="groupId" model="groupId" label="Customer group" :options="$groups" placeholder="None"/>
                    <x-select-field name="salesRepId" model="salesRepId" label="Sales representative" :options="$staff" placeholder="None"/>
                    <x-field name="paymentTerms" model="paymentTerms" label="Payment terms" placeholder="e.g. Net 30"/>
                    <x-textarea-field name="internalNotes" model="internalNotes" label="Internal notes" rows="3"/>
                </fieldset>
                @if ($canManage)<button class="btn btn-primary">Save</button>@endif
            </form>
            <section class="panel text-sm">
                <h2 class="mb-3 text-2xl">Users</h2>
                <ul class="divide-y divide-border">
                    @foreach ($company->users as $m)
                        <li class="flex items-center justify-between gap-2 py-2">
                            <span>{{ $m->name }}<span class="block text-xs text-muted">{{ $m->email }}</span></span>
                            @if ($canManage)
                                <span class="flex items-center gap-1">
                                    <select class="input py-1 text-xs" wire:change="setMemberRole({{ $m->id }}, $event.target.value)" aria-label="Company role"><option value="owner" @selected($m->company_role === 'owner')>Owner</option><option value="member" @selected($m->company_role !== 'owner')>Member</option></select>
                                    <button type="button" wire:click="removeMember({{ $m->id }})" wire:confirm="Remove {{ $m->email }} from this company?" class="text-muted hover:text-danger" aria-label="Remove user"><x-icon name="x" class="h-4 w-4"/></button>
                                </span>
                            @else
                                <x-badge>{{ $m->company_role }}</x-badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($canManage)
                    <form wire:submit="addMember" class="mt-4 flex items-end gap-2"><x-field name="memberEmail" model="memberEmail" type="email" label="Add registered user by email" class="flex-1"/><button class="btn btn-secondary btn-sm">Add</button></form>
                @endif
            </section>
        </aside>
    </div>
</div>
