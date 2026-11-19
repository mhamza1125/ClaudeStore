<div>
    <x-page-header title="Homepage slides" eyebrow="Content">
        <x-slot:actions>
            <a href="{{ route('home') }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="h-4 w-4"/> View homepage</a>
            <button type="button" wire:click="create" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> New slide</button>
        </x-slot:actions>
    </x-page-header>
    @if (session('status'))<div class="mb-4 alert badge-success" role="status">{{ session('status') }}</div>@endif

    <div class="grid gap-6 xl:grid-cols-5">
        <section class="space-y-3 xl:col-span-3" aria-label="Slides">
            @forelse ($slides as $slide)
                @php $img = $slide->imageUrl(); $live = in_array($slide->id, $liveIds, true); @endphp
                <article wire:key="slide-{{ $slide->id }}" class="card flex gap-4 p-3 {{ $editingId === $slide->id ? 'border-accent' : '' }}">
                    <div class="aspect-[4/3] w-36 shrink-0 overflow-hidden rounded-sm bg-elevated">
                        @if ($img)<img src="{{ $img }}" alt="" class="h-full w-full object-cover" style="object-position: {{ $slide->image_focus }}">@endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :tone="$live ? 'success' : 'neutral'">{{ $live ? 'Live' : ($slide->is_active ? 'Scheduled' : 'Hidden') }}</x-badge>
                            @if ($slide->starts_at || $slide->ends_at)<span class="text-xs text-muted">{{ $slide->starts_at?->format('j M Y H:i') ?? 'now' }} → {{ $slide->ends_at?->format('j M Y H:i') ?? 'open' }}</span>@endif
                        </div>
                        <p class="mt-1 truncate font-serif text-xl">{{ $slide->title }}</p>
                        <p class="truncate text-xs text-muted">{{ $slide->primary_label }}@if ($slide->secondary_label) · {{ $slide->secondary_label }}@endif @if ($slide->product) · {{ $slide->product->sku }}@endif</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <button type="button" wire:click="edit({{ $slide->id }})" class="btn btn-secondary btn-sm">Edit</button>
                            <button type="button" wire:click="toggle({{ $slide->id }})" class="btn btn-ghost btn-sm">{{ $slide->is_active ? 'Hide' : 'Show' }}</button>
                            <button type="button" wire:click="move({{ $slide->id }}, -1)" class="btn btn-ghost btn-sm" aria-label="Move up"><x-icon name="chevron-left" class="h-3 w-3 rotate-90"/></button>
                            <button type="button" wire:click="move({{ $slide->id }}, 1)" class="btn btn-ghost btn-sm" aria-label="Move down"><x-icon name="chevron-right" class="h-3 w-3 rotate-90"/></button>
                            <button type="button" wire:click="delete({{ $slide->id }})" wire:confirm="Delete this slide?" class="btn btn-ghost btn-sm text-danger" aria-label="Delete slide"><x-icon name="trash" class="h-3 w-3"/></button>
                        </div>
                    </div>
                </article>
            @empty
                <x-empty title="No slides yet" icon="image">The homepage shows a default banner until you add a slide.</x-empty>
            @endforelse
            <p class="text-xs text-muted">Slides play in this order. Autoplay speed is in Settings → Homepage.</p>
        </section>

        <form wire:submit="save" class="panel space-y-4 xl:col-span-2">
            <h2 class="text-2xl">{{ $editingId ? 'Edit slide' : 'New slide' }}</h2>
            <x-field name="form.eyebrow" model="form.eyebrow" label="Eyebrow" placeholder="e.g. New season"/>
            <x-field name="form.title" model="form.title" label="Heading" required/>
            <x-textarea-field name="form.subtitle" model="form.subtitle" label="Text" rows="3"/>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-field name="form.primary_label" model="form.primary_label" label="Main button text"/>
                <x-field name="form.primary_url" model="form.primary_url" label="Main button link" placeholder="/products"/>
                <x-field name="form.secondary_label" model="form.secondary_label" label="Second button text"/>
                <x-field name="form.secondary_url" model="form.secondary_url" label="Second button link" placeholder="/quote"/>
            </div>
            <div>
                <label for="hero-image" class="field-label">Banner image</label>
                @if ($editing?->image_path)
                    <div class="mb-2 flex items-center gap-3"><img src="{{ $editing->imageUrl() }}" alt="" class="h-16 w-24 rounded-sm object-cover"><button type="button" wire:click="removeImage" class="link text-xs">Remove image</button></div>
                @endif
                <input id="hero-image" type="file" wire:model="image" accept="image/jpeg,image/png,image/webp" class="input">
                <p class="field-help">Landscape, at least 800 × 500 px (1600 × 1200 recommended). Shown in a 4:3 frame and saved as optimised WebP.</p>
                @error('image')<p class="field-error">{{ $message }}</p>@enderror
                <div wire:loading wire:target="image" class="text-xs text-muted">Uploading…</div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-field name="productSku" model="productSku" label="Linked product SKU" placeholder="Optional" help="Adds a product link; its photo is used when no banner image is uploaded."/>
                <x-select-field name="form.image_focus" model="form.image_focus" label="Image focal point" :options="\App\Models\HeroSlide::FOCUS" help="Which part stays visible when cropped."/>
            </div>
            <x-field name="form.image_alt" model="form.image_alt" label="Image description (alt text)"/>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-field name="form.starts_at" model="form.starts_at" type="datetime-local" label="Show from" help="Optional, for promotions."/>
                <x-field name="form.ends_at" model="form.ends_at" type="datetime-local" label="Show until"/>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model="form.is_active"> Active</label>
            <div class="flex gap-2"><button class="btn btn-primary" wire:loading.attr="disabled">Save slide</button>@if ($editingId)<button type="button" wire:click="create" class="btn btn-ghost">Cancel</button>@endif</div>
        </form>
    </div>
</div>
