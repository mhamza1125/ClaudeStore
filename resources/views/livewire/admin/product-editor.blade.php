<div>
    <x-page-header :title="$product?->name ?? 'New product'" :eyebrow="$product ? 'Edit product · '.$product->sku : 'Catalogue'">
        <x-slot:actions>
            @if ($product)
                <x-badge :tone="['published' => 'success', 'draft' => 'neutral', 'archived' => 'danger'][$product->status]" class="text-sm">{{ ucfirst($product->status) }}</x-badge>
                <a href="{{ route('products.show', $product) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="h-4 w-4"/> {{ $product->isPublished() ? 'View' : 'Preview' }}</a>
                <button type="button" wire:click="duplicate" class="btn btn-secondary btn-sm">Duplicate</button>
                @if ($product->status !== 'published')<button type="button" wire:click="setStatus('published')" class="btn btn-accent btn-sm">Publish</button>@endif
                @if ($product->status !== 'archived')<button type="button" wire:click="setStatus('archived')" wire:confirm="Archive this product? It will be hidden from the storefront." class="btn btn-danger btn-sm">Archive</button>@endif
            @endif
        </x-slot:actions>
    </x-page-header>
    @if (session('status'))<div class="mb-4 badge badge-success w-full justify-start px-4 py-3 text-sm" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="mb-4 badge badge-danger w-full justify-start px-4 py-3 text-sm" role="alert">Please fix the highlighted fields ({{ $errors->count() }}).</div>@endif
    @if ($product?->is_demo)<div class="mb-4 badge badge-warning w-full justify-start px-4 py-3 text-sm">Demonstration product — replace its data before launch or archive it.</div>@endif

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <section class="panel grid gap-4 sm:grid-cols-2">
                <h2 class="text-2xl sm:col-span-2">Basics</h2>
                <x-field name="form.name" model="form.name" label="Name" required class="sm:col-span-2"/>
                <x-field name="form.sku" model="form.sku" label="SKU" required help="Letters, numbers, . _ -"/>
                <x-field name="form.product_code" model="form.product_code" label="Product code"/>
                <x-field name="form.slug" model="form.slug" label="URL slug" required/>
                <x-select-field name="form.category_id" model="form.category_id" label="Category" :options="$categories" placeholder="None"/>
                <x-textarea-field name="form.short_description" model="form.short_description" label="Short description" rows="2" class="sm:col-span-2"/>
                <x-textarea-field name="form.description" model="form.description" label="Description" rows="6" class="sm:col-span-2"/>
            </section>

            <section class="panel grid gap-4 sm:grid-cols-2">
                <h2 class="text-2xl sm:col-span-2">Materials &amp; specifications</h2>
                <p class="text-sm text-muted sm:col-span-2">Only enter verified facts. Leave a field empty rather than guessing — empty fields are hidden on the storefront.</p>
                <x-field name="form.material" model="form.material" label="Material" placeholder="e.g. Full-grain leather"/>
                <x-field name="form.leather_type" model="form.leather_type" label="Leather type"/>
                <x-field name="form.dimensions" model="form.dimensions" label="Size / dimensions"/>
                <x-field name="form.hardware" model="form.hardware" label="Hardware"/>
                <x-field name="form.finishing" model="form.finishing" label="Finishing"/>
                <x-field name="form.packaging" model="form.packaging" label="Packaging"/>
                <x-field name="form.lead_time" model="form.lead_time" label="Production / lead time" class="sm:col-span-2"/>
                <div class="sm:col-span-2">
                    <p class="field-label">Additional attributes</p>
                    @foreach ($specs as $i => $spec)
                        <div class="mb-2 flex gap-2" wire:key="spec-{{ $i }}">
                            <input wire:model.blur="specs.{{ $i }}.label" class="input" placeholder="Label" aria-label="Attribute label">
                            <input wire:model.blur="specs.{{ $i }}.value" class="input" placeholder="Value" aria-label="Attribute value">
                            <button type="button" wire:click="removeSpec({{ $i }})" class="btn btn-ghost btn-sm" aria-label="Remove attribute"><x-icon name="x" class="h-4 w-4"/></button>
                        </div>
                        @error("specs.$i.label")<p class="field-error">{{ $message }}</p>@enderror
                    @endforeach
                    <button type="button" wire:click="addSpec" class="btn btn-ghost btn-sm"><x-icon name="plus" class="h-4 w-4"/> Add attribute</button>
                </div>
            </section>

            <section class="panel">
                <div class="mb-4 flex items-center justify-between"><h2 class="text-2xl">Variants</h2><button type="button" wire:click="addVariant" class="btn btn-secondary btn-sm"><x-icon name="plus" class="h-4 w-4"/> Add variant</button></div>
                <p class="mb-4 text-sm text-muted">Colours, sizes and finishes. If a product has active variants, buyers must choose one. Variant price and MOQ override the product values when set.</p>
                <div class="space-y-4">
                    @forelse ($variants as $key => $v)
                        <div class="grid gap-3 rounded-sm border border-border p-4 sm:grid-cols-4" wire:key="variant-{{ $key }}">
                            <x-field :name="'variants.'.$key.'.name'" :model="'variants.'.$key.'.name'" label="Name" required class="sm:col-span-2"/>
                            <x-field :name="'variants.'.$key.'.sku'" :model="'variants.'.$key.'.sku'" label="Variant SKU" required class="sm:col-span-2"/>
                            <x-field :name="'variants.'.$key.'.color'" :model="'variants.'.$key.'.color'" label="Colour"/>
                            <x-field :name="'variants.'.$key.'.size'" :model="'variants.'.$key.'.size'" label="Size"/>
                            <x-field :name="'variants.'.$key.'.finish'" :model="'variants.'.$key.'.finish'" label="Finish / hardware"/>
                            <x-field :name="'variants.'.$key.'.price'" :model="'variants.'.$key.'.price'" label="Base price (USD)" inputmode="decimal"/>
                            <x-field :name="'variants.'.$key.'.moq'" :model="'variants.'.$key.'.moq'" label="MOQ" type="number"/>
                            <x-field :name="'variants.'.$key.'.stock_quantity'" :model="'variants.'.$key.'.stock_quantity'" label="Stock" type="number"/>
                            <x-select-field :name="'variants.'.$key.'.availability'" :model="'variants.'.$key.'.availability'" label="Availability" :options="$availabilities" placeholder="As product"/>
                            <div class="flex items-end justify-between gap-2">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model="variants.{{ $key }}.is_active"> Active</label>
                                <button type="button" wire:click="removeVariant('{{ $key }}')" wire:confirm="Remove this variant? Its tiers are removed too." class="btn btn-ghost btn-sm text-danger" aria-label="Remove variant"><x-icon name="trash" class="h-4 w-4"/></button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No variants. The product is quoted as a single item.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel">
                <div class="mb-2 flex items-center justify-between"><h2 class="text-2xl">Quantity price tiers</h2><button type="button" wire:click="addTier" class="btn btn-secondary btn-sm"><x-icon name="plus" class="h-4 w-4"/> Add tier</button></div>
                <p class="mb-4 text-sm text-muted">Each tier applies from its minimum quantity until the next tier. Variant tiers replace product tiers for that variant. Group tiers apply only to approved accounts in that customer group. See docs/PRICING.md.</p>
                @if ($tiers)
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead><tr><th>Applies to</th><th>Customer group</th><th class="w-32">From qty</th><th class="w-36">Unit price (USD)</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($tiers as $i => $t)
                                <tr wire:key="tier-{{ $i }}">
                                    <td><label class="sr-only" for="tv{{ $i }}">Variant</label><select id="tv{{ $i }}" wire:model="tiers.{{ $i }}.variant" class="input py-1.5"><option value="">All variants (product)</option>@foreach ($variants as $key => $v)<option value="{{ $key }}">{{ $v['name'] ?: 'New variant' }}</option>@endforeach</select></td>
                                    <td><label class="sr-only" for="tg{{ $i }}">Group</label><select id="tg{{ $i }}" wire:model="tiers.{{ $i }}.group" class="input py-1.5"><option value="">Standard (everyone)</option>@foreach ($groups as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></td>
                                    <td><label class="sr-only" for="tq{{ $i }}">Minimum quantity</label><input id="tq{{ $i }}" type="number" min="1" wire:model.blur="tiers.{{ $i }}.min_qty" class="input py-1.5">@error("tiers.$i.min_qty")<p class="field-error">{{ $message }}</p>@enderror</td>
                                    <td><label class="sr-only" for="tp{{ $i }}">Unit price</label><input id="tp{{ $i }}" inputmode="decimal" wire:model.blur="tiers.{{ $i }}.price" class="input py-1.5">@error("tiers.$i.price")<p class="field-error">{{ $message }}</p>@enderror</td>
                                    <td><button type="button" wire:click="removeTier({{ $i }})" class="btn btn-ghost btn-sm" aria-label="Remove tier"><x-icon name="x" class="h-4 w-4"/></button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-muted">No tiers: the base price applies to every quantity.</p>
                @endif
                <p class="mt-4 text-xs text-muted">Company-specific negotiated prices are managed on each customer's page (Admin → Customers).</p>
            </section>
        </div>

        <aside class="space-y-6 xl:col-span-4">
            <section class="panel space-y-4">
                <h2 class="text-2xl">Status &amp; visibility</h2>
                <x-select-field name="form.status" model="form.status" label="Status" :options="['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']"/>
                @foreach (['quote_enabled' => 'Quote requests enabled', 'public_pricing' => 'Show prices publicly (otherwise approved trade accounts only)', 'price_on_request' => 'Price on request (hide all prices)', 'is_custom' => 'Custom / made-to-order product', 'customizable' => 'Offer “Request customisation”', 'is_featured' => 'Featured', 'is_bestseller' => 'Bestseller'] as $field => $label)
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="checkbox mt-0.5" wire:model="form.{{ $field }}"> {{ $label }}</label>
                @endforeach
            </section>
            <section class="panel space-y-4">
                <h2 class="text-2xl">Pricing &amp; ordering</h2>
                <x-field name="form.base_price" model="form.base_price" label="Base price (USD)" inputmode="decimal" help="Used when no tier applies."/>
                <div class="grid grid-cols-2 gap-3">
                    <x-field name="form.moq" model="form.moq" type="number" label="MOQ" min="1" required/>
                    <x-field name="form.order_multiple" model="form.order_multiple" type="number" label="Order multiple" min="1" required help="Case pack"/>
                </div>
            </section>
            <section class="panel space-y-4">
                <h2 class="text-2xl">Availability</h2>
                <x-select-field name="form.availability" model="form.availability" label="Availability" :options="$availabilities"/>
                <x-field name="form.stock_quantity" model="form.stock_quantity" type="number" label="Stock quantity" min="0" help="Optional; informational."/>
            </section>
            <section class="panel space-y-4">
                <h2 class="text-2xl">SEO</h2>
                <x-field name="form.meta_title" model="form.meta_title" label="Meta title"/>
                <x-textarea-field name="form.meta_description" model="form.meta_description" label="Meta description" rows="3"/>
            </section>
            <button type="submit" class="btn btn-primary btn-lg w-full" wire:loading.attr="disabled" wire:target="save">Save product</button>
        </aside>
    </form>

    @if ($product)
        {{-- Images --}}
        @php $processing = $product->images->where('status', 'processing'); @endphp
        {{-- Re-render while optimisation runs in the queue so the cards show the real status. --}}
        <section class="panel mt-8" aria-labelledby="img-h" @if ($processing->isNotEmpty()) wire:poll.3s @endif>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 id="img-h" class="text-2xl">Images</h2>
                <p class="text-xs text-muted">Image provider: <span class="font-medium text-text">{{ $provider->isConfigured() ? $provider->name() : 'not configured (manual workflow)' }}</span></p>
            </div>
            @error('images')<p class="field-error mb-3">{{ $message }}</p>@enderror
            <form wire:submit="uploadImages" class="mb-6 grid gap-3 rounded-sm border border-dashed border-border-strong p-4 md:grid-cols-5">
                <div class="md:col-span-2">
                    <label for="uploads" class="field-label">Upload photographs</label>
                    <input id="uploads" type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp" class="input">
                    <p class="field-help">JPG, PNG or WebP, min 300 px, max 15 MB. Originals are stored privately; an optimised WebP is published.</p>
                    @error('uploads')<p class="field-error">{{ $message }}</p>@enderror @error('uploads.*')<p class="field-error">{{ $message }}</p>@enderror
                    <div wire:loading wire:target="uploads" class="text-xs text-muted">Uploading…</div>
                </div>
                <x-select-field name="uploadViewType" model="uploadViewType" label="View (optional)" :options="$viewTypes" placeholder="Not specified"/>
                <x-select-field name="uploadVariant" model="uploadVariant" label="Variant" :options="$product->variants->pluck('name', 'id')" placeholder="All variants"/>
                <div class="flex flex-col justify-end gap-2">
                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" class="checkbox" wire:model="uploadAsDraft"> Upload as draft for review (e.g. retouched or AI-generated)</label>
                    <button class="btn btn-secondary btn-sm" wire:loading.attr="disabled" wire:target="uploads,uploadImages">
                        <span wire:loading.remove wire:target="uploadImages" class="inline-flex items-center gap-1"><x-icon name="upload" class="h-4 w-4"/> Upload</span>
                        <span wire:loading wire:target="uploadImages">Processing…</span>
                    </button>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($product->images as $img)
                    @php
                        // Uploads are processed during the request; still "processing" after a minute means it was interrupted.
                        $stalled = $img->status === 'processing' && $img->updated_at?->lt(now()->subMinute());
                        $canProcess = $img->source === 'upload' && $img->original_path && ($img->status === 'failed' || $stalled);
                    @endphp
                    <div class="rounded-sm border {{ $img->status === 'pending_review' ? 'border-warning' : ($img->status === 'failed' ? 'border-danger' : 'border-border') }} p-3 text-xs" wire:key="img-{{ $img->id }}">
                        <div class="relative aspect-[4/5] overflow-hidden rounded-sm bg-elevated">
                            @if ($img->url())<img src="{{ $img->url() }}" alt="{{ $img->alt }}" class="h-full w-full object-cover" loading="lazy">
                            @elseif ($img->status === 'failed')<div class="flex h-full items-center justify-center text-danger">Processing failed</div>
                            @else<div class="flex h-full flex-col items-center justify-center gap-2 text-muted"><span class="h-5 w-5 animate-spin rounded-full border-2 border-current border-t-transparent motion-reduce:animate-none" aria-hidden="true"></span>{{ $stalled ? 'Not finished' : 'Processing…' }}</div>@endif
                            <div class="absolute left-2 top-2 flex flex-wrap gap-1">
                                <x-badge :tone="['approved' => 'success', 'pending_review' => 'warning', 'rejected' => 'danger', 'failed' => 'danger', 'processing' => 'neutral'][$img->status] ?? 'neutral'">{{ ucfirst(str_replace('_', ' ', $img->status)) }}</x-badge>
                                @if ($img->source !== 'upload')<x-badge>{{ $img->source }}@if ($img->provider) · {{ $img->provider }}@endif</x-badge>@endif
                            </div>
                        </div>
                        @if ($img->provider_notes)<p class="mt-2 {{ $img->status === 'failed' ? 'text-danger' : 'text-muted' }}">{{ $img->provider_notes }}</p>@endif
                        @if ($stalled)<p class="mt-2 text-warning" role="status">Processing was interrupted before it finished. Process it now to complete it.</p>@endif
                        @if ($canProcess)
                            <button type="button" wire:click="processImageNow({{ $img->id }})" wire:loading.attr="disabled" wire:target="processImageNow({{ $img->id }})" class="btn btn-secondary btn-sm mt-2 w-full">
                                <span wire:loading.remove wire:target="processImageNow({{ $img->id }})">{{ $img->status === 'failed' ? 'Retry processing' : 'Process now' }}</span>
                                <span wire:loading wire:target="processImageNow({{ $img->id }})">Processing…</span>
                            </button>
                        @endif
                        @if ($img->parent_id)<p class="mt-1 text-muted">Derived from image #{{ $img->parent_id }} — compare against the original before approving (logo, hardware, stitching, colour).</p>@endif
                        <div class="mt-2 grid gap-2">
                            <label class="sr-only" for="vt{{ $img->id }}">View type</label>
                            <select id="vt{{ $img->id }}" class="input py-1 text-xs" wire:change="updateImage({{ $img->id }}, 'view_type', $event.target.value)"><option value="">No view specified</option>@foreach ($viewTypes as $v => $l)<option value="{{ $v }}" @selected($img->view_type?->value === $v)>{{ $l }}</option>@endforeach</select>
                            <label class="sr-only" for="iv{{ $img->id }}">Variant</label>
                            <select id="iv{{ $img->id }}" class="input py-1 text-xs" wire:change="updateImage({{ $img->id }}, 'variant_id', $event.target.value)"><option value="">All variants</option>@foreach ($product->variants as $variant)<option value="{{ $variant->id }}" @selected($img->variant_id === $variant->id)>{{ $variant->name }}</option>@endforeach</select>
                            <label class="sr-only" for="ia{{ $img->id }}">Alt text</label>
                            <input id="ia{{ $img->id }}" class="input py-1 text-xs" value="{{ $img->alt }}" placeholder="Alt text" wire:change="updateImage({{ $img->id }}, 'alt', $event.target.value)">
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @if ($img->path && $img->status !== 'approved')<button type="button" wire:click="setImageStatus({{ $img->id }}, 'approved')" class="btn btn-secondary btn-sm">Approve</button>@endif
                            @if ($img->path && $img->status !== 'rejected')<button type="button" wire:click="setImageStatus({{ $img->id }}, 'rejected')" class="btn btn-ghost btn-sm">Reject</button>@endif
                            <button type="button" wire:click="moveImage({{ $img->id }}, -1)" class="btn btn-ghost btn-sm" aria-label="Move earlier"><x-icon name="chevron-left" class="h-3 w-3"/></button>
                            <button type="button" wire:click="moveImage({{ $img->id }}, 1)" class="btn btn-ghost btn-sm" aria-label="Move later"><x-icon name="chevron-right" class="h-3 w-3"/></button>
                            @if ($provider->isConfigured() && in_array('remove_background', $provider->capabilities()) && $img->source === 'upload' && $img->path)
                                <button type="button" wire:click="removeBackground({{ $img->id }})" class="btn btn-ghost btn-sm">Remove background</button>
                            @endif
                            <button type="button" wire:click="deleteImage({{ $img->id }})" wire:confirm="Delete this image{{ $img->source === 'upload' ? ' and its stored original' : '' }}?" class="btn btn-ghost btn-sm text-danger" aria-label="Delete image"><x-icon name="trash" class="h-3 w-3"/></button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted sm:col-span-4">No images yet.</p>
                @endforelse
            </div>
            @unless ($provider->isConfigured())
                <p class="mt-4 text-xs text-muted">Background removal and AI generation are disabled because no provider is configured. To use edited or generated images, create them externally and upload them here as drafts; approve them only after checking they match the real product. See docs/IMAGES.md.</p>
            @endunless
        </section>

        <section class="panel mt-6">
            <h2 class="mb-3 text-2xl">Specification sheet</h2>
            @if ($product->spec_sheet_path)
                <p class="text-sm">A PDF is attached. <a class="link" href="{{ route('products.spec-sheet', $product) }}" target="_blank">Download</a> · <button type="button" class="link text-danger" wire:click="removeSpecSheet" wire:confirm="Remove the specification sheet?">Remove</button></p>
            @endif
            <form wire:submit="uploadSpecSheet" class="mt-3 flex flex-wrap items-end gap-3">
                <div><label for="spec" class="field-label">PDF (max 10 MB)</label><input id="spec" type="file" wire:model="specSheet" accept="application/pdf" class="input">@error('specSheet')<p class="field-error">{{ $message }}</p>@enderror</div>
                <button class="btn btn-secondary btn-sm">Upload</button>
            </form>
        </section>
    @endif
</div>
