<div>
    <x-page-header title="Categories" eyebrow="Catalogue"/>
    @if (session('status'))<div class="mb-4 alert badge-success">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="mb-4 alert badge-danger">{{ session('error') }}</div>@endif
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            <table class="table">
                <thead><tr><th>Category</th><th>Slug</th><th>Products</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($categories as $cat)
                    @foreach ([$cat, ...$cat->children] as $c)
                        <tr wire:key="c-{{ $c->id }}">
                            <td class="{{ $c->parent_id ? 'pl-10 text-muted' : 'font-medium' }}">{{ $c->parent_id ? '— ' : '' }}{{ $c->name }}</td>
                            <td class="text-muted">{{ $c->slug }}</td>
                            <td>{{ $c->products_count }}</td>
                            <td>@if ($c->is_active)<x-badge tone="success">Active</x-badge>@else<x-badge>Hidden</x-badge>@endif @if ($c->is_featured)<x-badge tone="accent">Featured</x-badge>@endif</td>
                            <td class="whitespace-nowrap text-right"><button type="button" wire:click="edit({{ $c->id }})" class="btn btn-ghost btn-sm">Edit</button><button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Delete {{ $c->name }}?" class="btn btn-ghost btn-sm text-danger">Delete</button></td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
        <form wire:submit="save" class="panel space-y-4">
            <h2 class="text-2xl">{{ $editingId ? 'Edit category' : 'New category' }}</h2>
            <x-field name="form.name" model="form.name" label="Name" required/>
            <x-field name="form.slug" model="form.slug" label="Slug" help="Leave empty to generate from the name."/>
            <x-select-field name="form.parent_id" model="form.parent_id" label="Parent" :options="$parents" placeholder="Top level"/>
            <x-textarea-field name="form.description" model="form.description" label="Description" rows="3"/>
            <x-field name="form.sort_order" model="form.sort_order" type="number" label="Sort order" min="0"/>
            <div><label for="cimg" class="field-label">Image</label><input id="cimg" type="file" wire:model="image" accept="image/*" class="input">@error('image')<p class="field-error">{{ $message }}</p>@enderror</div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model="form.is_active"> Active</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model="form.is_featured"> Featured on homepage</label>
            <div class="flex gap-2"><button class="btn btn-primary">Save</button>@if ($editingId)<button type="button" wire:click="cancel" class="btn btn-ghost">Cancel</button>@endif</div>
        </form>
    </div>
</div>
