<x-layouts.account title="Company & addresses">
    <x-page-header title="Company & addresses" eyebrow="Account"/>
    @php $canEdit = ! $company || $user->isCompanyOwner(); $types = array_combine(\App\Http\Requests\QuoteSubmissionRequest::BUSINESS_TYPES, \App\Http\Requests\QuoteSubmissionRequest::BUSINESS_TYPES); @endphp
    <form method="POST" action="{{ route('account.company.update') }}" class="panel space-y-4">
        @csrf @method('PUT')
        <h2 class="text-2xl">{{ $company ? 'Company profile' : 'Create your company profile' }}</h2>
        <fieldset @disabled(! $canEdit) class="grid gap-4 sm:grid-cols-2">
            <x-field name="name" label="Company name" required :value="$company?->name"/>
            <x-field name="email" type="email" label="Company email" :value="$company?->email"/>
            <x-field name="phone" label="Phone" :value="$company?->phone"/>
            <x-field name="website" type="url" label="Website" :value="$company?->website"/>
            <x-select-field name="country" label="Country" required :options="countries()" placeholder="Select…" :value="$company?->country"/>
            <x-field name="vat_number" label="VAT / tax ID" :value="$company?->vat_number"/>
            <x-select-field name="business_type" label="Business type" :options="$types" placeholder="Select…" :value="$company?->business_type"/>
        </fieldset>
        @if ($canEdit)<button class="btn btn-primary">Save company</button>@else<p class="text-sm text-muted">Only the company owner can edit these details.</p>@endif
    </form>

    @if ($company)
        <section id="wholesale" class="panel mt-6">
            <h2 class="text-2xl">Wholesale pricing access</h2>
            <p class="mt-1 text-sm">Status: <x-badge :tone="match($company->wholesale_status) { 'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'neutral' }">{{ $company->wholesale_status === 'none' ? 'Not requested' : ucfirst($company->wholesale_status) }}</x-badge></p>
            @if (in_array($company->wholesale_status, ['none', 'rejected']) && $user->isCompanyOwner())
                <form method="POST" action="{{ route('account.company.wholesale') }}" class="mt-4 space-y-3">
                    @csrf
                    <x-textarea-field name="wholesale_application" label="Tell us about your business" rows="4" required placeholder="Stores or channels, typical order volumes, product categories of interest…"/>
                    <button class="btn btn-primary">Request wholesale access</button>
                </form>
            @elseif ($company->wholesale_status === 'pending')
                <p class="mt-2 text-sm text-muted">Submitted {{ $company->wholesale_requested_at?->format('j M Y') }}. We will email you once reviewed.</p>
            @endif
        </section>

        <section class="panel mt-6">
            <h2 class="mb-4 text-2xl">Billing &amp; shipping addresses</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($company->addresses as $a)
                    <div class="rounded-sm border border-border p-4 text-sm" x-data="{ edit: false }">
                        <div class="flex items-start justify-between gap-2">
                            <div><x-badge>{{ ucfirst($a->type) }}</x-badge> @if ($a->is_default)<x-badge tone="accent">Default</x-badge>@endif
                                <p class="mt-2 font-medium">{{ $a->label ?: $a->recipient }}</p><p class="text-muted">{{ $a->singleLine() }}</p></div>
                            @if ($canEdit)
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-ghost btn-sm" @click="edit = !edit">Edit</button>
                                    <form method="POST" action="{{ route('account.addresses.destroy', $a) }}" onsubmit="return confirm('Delete this address?')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger" aria-label="Delete address"><x-icon name="trash" class="h-4 w-4"/></button></form>
                                </div>
                            @endif
                        </div>
                        @if ($canEdit)
                            <form x-show="edit" x-cloak method="POST" action="{{ route('account.addresses.update', $a) }}" class="mt-4">@csrf @method('PUT') @include('account.partials.address-fields', ['address' => $a, 'prefix' => 'a'.$a->id])<button class="btn btn-primary btn-sm mt-3">Save address</button></form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-muted">No addresses yet.</p>
                @endforelse
            </div>
            @if ($canEdit)
                <details class="mt-6"><summary class="cursor-pointer text-sm font-medium text-accent">Add an address</summary>
                    <form method="POST" action="{{ route('account.addresses.store') }}" class="mt-4">@csrf @include('account.partials.address-fields', ['address' => null, 'prefix' => 'new'])<button class="btn btn-primary btn-sm mt-3">Add address</button></form>
                </details>
            @endif
        </section>
    @endif
</x-layouts.account>
