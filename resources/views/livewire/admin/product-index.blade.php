<div>
    <x-page-header title="Products" eyebrow="Catalogue">
        <x-slot:actions>
            <a href="{{ route('admin.csv.index') }}" class="btn btn-secondary btn-sm">Import / export CSV</a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> New product</a>
        </x-slot:actions>
    </x-page-header>
    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <div><label for="ps" class="sr-only">Search</label><input id="ps" type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="Name or SKU"></div>
        <x-select-field name="status" model="status" :options="['published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived']" placeholder="All statuses" aria-label="Status"/>
        <x-select-field name="category" model="category" :options="$categories" placeholder="All categories" aria-label="Category"/>
    </div>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th></th><th>Product</th><th>Category</th><th>Base price</th><th>MOQ</th><th>Variants / tiers</th><th>Pricing</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                <tr wire:key="p-{{ $p->id }}">
                    <td>@if ($img = $p->primaryImage())<img src="{{ $img->adminUrl('thumb') }}" alt="" class="h-12 w-10 rounded-sm object-cover">@endif</td>
                    <td><a href="{{ route('admin.products.edit', $p) }}" class="font-medium hover:text-accent">{{ $p->name }}</a><span class="block text-xs text-muted">{{ $p->sku }}@if ($p->is_demo) · demo @endif</span></td>
                    <td class="text-muted">{{ $p->category?->name ?? '—' }}</td>
                    <td>{{ money($p->base_price_cents) }}</td>
                    <td>{{ $p->moq }}</td>
                    <td class="text-muted">{{ $p->variants_count }} / {{ $p->price_tiers_count }}</td>
                    <td class="text-xs">@if ($p->price_on_request)<x-badge tone="accent">On request</x-badge>@elseif (! $p->public_pricing)<x-badge>Trade only</x-badge>@else<x-badge tone="success">Public</x-badge>@endif</td>
                    <td><x-badge :tone="['published' => 'success', 'draft' => 'neutral', 'archived' => 'danger'][$p->status]">{{ ucfirst($p->status) }}</x-badge></td>
                    <td class="whitespace-nowrap text-right">
                        @if ($p->status !== 'published')<button type="button" wire:click="setStatus({{ $p->id }}, 'published')" class="btn btn-ghost btn-sm">Publish</button>@endif
                        @if ($p->status !== 'archived')<button type="button" wire:click="setStatus({{ $p->id }}, 'archived')" wire:confirm="Archive {{ $p->name }}?" class="btn btn-ghost btn-sm">Archive</button>@endif
                        <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-secondary btn-sm">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-10 text-center text-muted">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</div>
