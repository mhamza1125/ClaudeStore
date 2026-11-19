<div>
    <x-page-header title="Products" eyebrow="Catalogue">
        <x-slot:actions>
            <a href="{{ route('admin.csv.index') }}" class="btn btn-secondary btn-sm">Import / export CSV</a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> New product</a>
        </x-slot:actions>
    </x-page-header>
    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div><label for="ps" class="sr-only">Search</label><input id="ps" type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="Name or SKU"></div>
        <x-select-field name="status" model="status" :options="['published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived']" placeholder="All statuses" aria-label="Status"/>
        <x-select-field name="category" model="category" :options="$categories" placeholder="All categories" aria-label="Category"/>
        <x-select-field name="type" model="type" :options="['finished' => 'Finished goods', 'raw' => 'Raw leather']" placeholder="All product types" aria-label="Product type"/>
    </div>

    @if ($notice)
        <div class="mb-4 flex items-start justify-between gap-4 alert badge-success" role="status">
            <p>{{ $notice }}</p>
            <button type="button" wire:click="$set('notice', '')" class="shrink-0" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4"/></button>
        </div>
    @endif

    {{-- Bulk actions bar: appears once at least one product is selected. --}}
    @if (count($selected))
        <form wire:submit="applyBulk" class="sticky top-0 z-10 mb-4 flex flex-wrap items-end gap-3 rounded-sm border border-border-strong bg-surface p-3 shadow-sm" aria-label="Bulk actions">
            <p class="mr-auto self-center text-sm"><span class="font-semibold">{{ count($selected) }}</span> selected
                @if ($products->total() > count($selected))
                    · <button type="button" wire:click="selectAllMatching" class="link">Select all {{ number_format(min($products->total(), 500)) }} matching</button>
                @endif
                · <button type="button" wire:click="clearSelection" class="link">Clear</button>
            </p>
            <div>
                <label for="bulk-action" class="sr-only">Bulk action</label>
                <select id="bulk-action" wire:model.live="bulkAction" class="input py-1.5 text-sm">
                    <option value="">Choose an action…</option>
                    @foreach ($bulkActions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            @if ($bulkAction === 'category')
                <div>
                    <label for="bulk-category" class="sr-only">Target category</label>
                    <select id="bulk-category" wire:model="bulkCategory" class="input py-1.5 text-sm">
                        <option value="">Choose a category…</option>
                        @foreach ($categories as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                </div>
            @endif
            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="applyBulk"
                    @if (in_array($bulkAction, ['archive', 'draft'], true)) wire:confirm="{{ $bulkActions[$bulkAction] }}: apply to {{ count($selected) }} selected products?" @endif>Apply</button>
            @error('bulkAction')<p class="field-error w-full">{{ $message }}</p>@enderror
            @error('bulkCategory')<p class="field-error w-full">{{ $message }}</p>@enderror
        </form>
    @endif

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr>
                <th class="w-8"><input type="checkbox" class="checkbox" aria-label="Select all products on this page" @checked($pageSelected) @disabled(! $pageIds) wire:click="togglePage(@js($pageIds))"></th>
                <th></th><th>Product</th><th>Category</th><th>Base price</th><th>MOQ</th><th>Variants / tiers</th><th>Pricing</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($products as $p)
                <tr wire:key="p-{{ $p->id }}" @class(['bg-elevated/60' => in_array((string) $p->id, $selected, true)])>
                    <td><input type="checkbox" class="checkbox" value="{{ $p->id }}" wire:model.live="selected" aria-label="Select {{ $p->name }}"></td>
                    <td>@if ($img = $p->primaryImage())<img src="{{ $img->adminUrl('thumb') }}" alt="" class="h-12 w-10 rounded-sm object-cover">@endif</td>
                    <td>
                        <a href="{{ route('admin.products.edit', $p) }}" class="font-medium hover:text-accent">{{ $p->name }}</a>
                        <span class="block text-xs text-muted">{{ $p->sku }}@if ($p->is_demo) · demo @endif</span>
                        @if ($p->is_raw_leather || $p->is_featured || $p->is_bestseller)
                            <span class="mt-1 flex flex-wrap gap-1">
                                @if ($p->is_raw_leather)<x-badge tone="accent">Raw leather</x-badge>@endif
                                @if ($p->is_featured)<x-badge>Featured</x-badge>@endif
                                @if ($p->is_bestseller)<x-badge>Bestseller</x-badge>@endif
                            </span>
                        @endif
                    </td>
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
                <tr><td colspan="10" class="py-10 text-center text-muted">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</div>
