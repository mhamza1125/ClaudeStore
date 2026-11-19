<div>
    <x-page-header title="Customer accounts" eyebrow="Registrations & price access">
        @can('settings.manage')
            <x-slot:actions><a href="{{ route('admin.b2b') }}" class="btn btn-secondary btn-sm"><x-icon name="settings" class="h-4 w-4"/> B2B settings</a></x-slot:actions>
        @endcan
    </x-page-header>
    @if (session('status'))<div class="mb-4 alert badge-success" role="status">{{ session('status') }}</div>@endif

    <p class="mb-6 text-sm text-muted" data-testid="access-summary">
        @if ($method)
            B2B pricing is <strong class="text-text">on</strong> · access method: <strong class="text-text">{{ $method }}</strong>.
        @else
            B2B pricing is <strong class="text-text">off</strong>: prices follow each product’s visibility and company wholesale approval. Overrides and suspensions below still apply.
        @endif
    </p>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div><label for="ca-search" class="sr-only">Search</label><input id="ca-search" type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="Name, email or company"></div>
        <x-select-field name="status" model="status" :options="\App\Livewire\Admin\CustomerAccounts::STATUSES" placeholder="All customers" aria-label="Filter"/>
    </div>

    <div class="card overflow-x-auto">
        <table class="table text-sm">
            <thead><tr><th>Customer</th><th>Company</th><th>Email</th><th>Approval</th><th>Prices</th><th>Override</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            @forelse ($customers as $u)
                @php $decision = $access->for($u); @endphp
                <tr wire:key="cu-{{ $u->id }}">
                    <td>
                        <span class="font-medium">{{ $u->name }}</span>
                        <span class="block text-xs text-muted">{{ $u->email }}@if ($u->phone) · {{ $u->phone }}@endif</span>
                        <span class="block text-xs text-muted">Registered {{ $u->created_at?->format('j M Y') }} · #{{ $u->id }}</span>
                    </td>
                    <td>@if ($u->company)<a href="{{ route('admin.companies.show', $u->company) }}" class="hover:text-accent">{{ $u->company->name }}</a>@else<span class="text-muted">—</span>@endif</td>
                    <td>
                        @if ($u->hasVerifiedEmail())<x-badge tone="success">Verified</x-badge>
                        @else<x-badge tone="warning">Unverified</x-badge>@endif
                    </td>
                    <td>
                        <x-badge :tone="match($u->approval_status) { 'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'neutral' }">{{ ucfirst($u->approval_status ?? 'not reviewed') }}</x-badge>
                        @if (! $u->is_active)<x-badge tone="danger" class="mt-1">Suspended</x-badge>@endif
                    </td>
                    <td data-testid="decision-{{ $u->id }}">
                        @if ($decision->allowed)<x-badge tone="success">Can see prices</x-badge>
                        @else<x-badge>{{ match ($decision->reason) { 'verify' => 'Needs verification', 'approval' => 'Needs approval', 'rejected' => 'Rejected', default => 'Blocked' } }}</x-badge>@endif
                    </td>
                    <td>
                        @if ($canManage)
                            <select class="input py-1 text-xs" wire:change="setPriceAccess({{ $u->id }}, $event.target.value)" aria-label="Price access override for {{ $u->email }}">
                                @foreach (\App\Models\User::PRICE_ACCESS as $value => $label)<option value="{{ $value }}" @selected($u->price_access === $value)>{{ $label }}</option>@endforeach
                            </select>
                        @else
                            {{ \App\Models\User::PRICE_ACCESS[$u->price_access] ?? $u->price_access }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if ($canManage)
                            <div class="flex flex-wrap justify-end gap-1">
                                @if ($u->approval_status !== 'approved')<button type="button" wire:click="approve({{ $u->id }})" class="btn btn-primary btn-sm">Approve</button>@endif
                                @if ($u->approval_status !== 'rejected')<button type="button" wire:click="reject({{ $u->id }})" wire:confirm="Reject {{ $u->email }}? They will be emailed." class="btn btn-ghost btn-sm">Reject</button>@endif
                                @unless ($u->hasVerifiedEmail())
                                    <button type="button" wire:click="resendVerification({{ $u->id }})" class="btn btn-ghost btn-sm">Resend link</button>
                                    <button type="button" wire:click="markVerified({{ $u->id }})" wire:confirm="Mark {{ $u->email }} as verified without the email link?" class="btn btn-ghost btn-sm">Mark verified</button>
                                @endunless
                                <button type="button" wire:click="toggleActive({{ $u->id }})" @if ($u->is_active) wire:confirm="Suspend {{ $u->email }}? They will be signed out and cannot sign in." @endif class="btn btn-ghost btn-sm {{ $u->is_active ? 'text-danger' : '' }}">{{ $u->is_active ? 'Suspend' : 'Reactivate' }}</button>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-muted">No customer accounts match.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $customers->links() }}</div>
</div>
