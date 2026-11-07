<div>
    @php
        // Header links: every main collection (current one highlighted), then the subcategories
        // of the current collection.
        $currentRoot = $category?->parent ?? $category;
        $subcategories = $currentRoot ? ($categories->firstWhere('id', $currentRoot->id)?->children ?? collect()) : collect();
        $description = $category?->description ?: $category?->parent?->description ?: ($raw ? 'Hides, sides and skins priced by quotation. Add the leathers you need to a quote with your quantities and we will reply with pricing.' : null) ?: 'Premium leather bags, belts, wallets and accessories for retailers, distributors and brands.';
    @endphp
    <section class="catalog-hero border-b border-border bg-band text-on-band" data-testid="catalog-hero">
        <div class="container-page py-9 sm:py-12">
            <nav class="mb-4 text-xs text-on-band/70" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-on-band hover:underline">Home</a> /
                <a href="{{ route('catalog') }}" class="hover:text-on-band hover:underline">Products</a>
                @if ($category?->parent) / <a href="{{ route('catalog.category', $category->parent) }}" class="hover:text-on-band hover:underline">{{ $category->parent->name }}</a>@endif
                @if ($category) / <span class="text-on-band">{{ $category->name }}</span>@endif
            </nav>
            <p class="eyebrow mb-2 !text-on-band/70">{{ $category ? ($category->parent ? $category->parent->name : 'Collection') : 'Catalogue' }}</p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <h1 class="text-4xl leading-tight sm:text-5xl">{{ $category?->name ?? ($raw ? 'Raw leather' : 'All products') }}</h1>
                <span class="rounded-full border border-on-band/25 px-3 py-1 text-xs tracking-wide text-on-band/80" data-testid="catalog-count">
                    {{ number_format($products->total()) }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                </span>
            </div>
            {{-- One quiet line (two on small screens); the full text stays available on hover. --}}
            <p class="mt-3 max-w-5xl text-sm text-on-band/75 line-clamp-2 lg:line-clamp-1" title="{{ $description }}">{{ $description }}</p>

            <div class="mt-7 flex flex-wrap gap-2" aria-label="Collections">
                <a href="{{ route('catalog') }}" class="catalog-chip {{ ! $category && ! $raw ? 'is-active' : '' }}">All products</a>
                @foreach ($categories as $root)
                    <a href="{{ route('catalog.category', $root) }}" class="catalog-chip {{ $currentRoot?->id === $root->id ? 'is-active' : '' }}" @if ($currentRoot?->id === $root->id) aria-current="true" @endif>{{ $root->name }}</a>
                @endforeach
            </div>
            @if ($subcategories->isNotEmpty())
                <div class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-on-band/15 pt-4" aria-label="{{ $currentRoot->name }} subcategories">
                    <a href="{{ route('catalog.category', $currentRoot) }}" class="catalog-chip catalog-chip--sub {{ $category?->id === $currentRoot->id ? 'is-active' : '' }}">All {{ $currentRoot->name }}</a>
                    @foreach ($subcategories as $sub)
                        <a href="{{ route('catalog.category', $sub) }}" class="catalog-chip catalog-chip--sub {{ $category?->id === $sub->id ? 'is-active' : '' }}" @if ($category?->id === $sub->id) aria-current="page" @endif>{{ $sub->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <div class="container-page py-10">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
        <p class="text-sm text-muted">Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }}</p>
        <div class="flex flex-wrap items-center gap-2">
            <label for="sort" class="text-sm text-muted">Sort by</label>
            <select id="sort" wire:model.live="sort" class="input w-auto">
                <option value="featured">Featured</option>
                <option value="newest">Newest</option>
                <option value="name">Name A–Z</option>
                @if ($access->allowed)
                    <option value="price_asc">Price: low to high</option>
                    <option value="price_desc">Price: high to low</option>
                @endif
            </select>
            <div class="flex rounded-sm border border-border-strong" role="group" aria-label="Layout">
                <button type="button" wire:click="$set('view', 'grid')" class="btn btn-sm border-0 {{ $view === 'grid' ? 'bg-elevated' : '' }}" aria-pressed="{{ $view === 'grid' ? 'true' : 'false' }}"><x-icon name="grid" class="h-4 w-4"/><span class="sr-only">Grid view</span></button>
                <button type="button" wire:click="$set('view', 'list')" class="btn btn-sm border-0 {{ $view === 'list' ? 'bg-elevated' : '' }}" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"><x-icon name="list" class="h-4 w-4"/><span class="sr-only">List view with bulk quantity entry</span></button>
            </div>
        </div>
    </div>

    <div class="grid gap-10 lg:grid-cols-[240px_1fr]">
        <aside x-data="{ open: false }" class="lg:block" aria-label="Filters">
            <button type="button" class="btn btn-secondary mb-4 w-full lg:hidden" @click="open = !open" :aria-expanded="open">Filters</button>
            <div :class="open ? 'block' : 'hidden lg:block'" class="space-y-8 text-sm">
                <div>
                    <label for="catalog-q" class="eyebrow mb-3 block">Search</label>
                    <input id="catalog-q" type="search" wire:model.live.debounce.400ms="q" placeholder="Name, SKU or code" class="input">
                </div>
                <div>
                    <h2 class="eyebrow mb-3">Categories</h2>
                    <ul class="space-y-3.5">
                        <li><a href="{{ route('catalog') }}" class="{{ ! $category ? 'font-semibold text-accent' : 'hover:text-accent' }}">All products</a></li>
                        @foreach ($categories as $cat)
                            <li>
                                <a href="{{ route('catalog.category', $cat) }}" class="{{ $category?->id === $cat->id ? 'font-semibold text-accent' : 'hover:text-accent' }}">{{ $cat->name }}</a>
                                @if ($cat->children->isNotEmpty())
                                    <ul class="mt-2 mb-1 space-y-1.5 border-l border-border pl-3">
                                        @foreach ($cat->children as $child)
                                            <li><a href="{{ route('catalog.category', $child) }}" class="{{ $category?->id === $child->id ? 'font-semibold text-accent' : 'text-muted hover:text-accent' }}">{{ $child->name }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                @if ($materials->isNotEmpty())
                    <fieldset>
                        <legend class="eyebrow mb-3">Material</legend>
                        @foreach ($materials as $m)
                            <label class="flex items-center gap-2 py-1"><input type="checkbox" class="checkbox" value="{{ $m }}" wire:model.live="material"> {{ $m }}</label>
                        @endforeach
                    </fieldset>
                @endif
                @if ($colors->isNotEmpty())
                    <fieldset>
                        <legend class="eyebrow mb-3">Colour</legend>
                        @foreach ($colors as $c)
                            <label class="flex items-center gap-2 py-1"><input type="checkbox" class="checkbox" value="{{ $c }}" wire:model.live="color"> {{ $c }}</label>
                        @endforeach
                    </fieldset>
                @endif
                <fieldset>
                    <legend class="eyebrow mb-3">Availability</legend>
                    @foreach ($availabilities as $value => $label)
                        <label class="flex items-center gap-2 py-1"><input type="checkbox" class="checkbox" value="{{ $value }}" wire:model.live="availability"> {{ $label }}</label>
                    @endforeach
                </fieldset>
                @if ($access->allowed)
                    <fieldset>
                        <legend class="eyebrow mb-3">List price (USD)</legend>
                        <div class="flex items-center gap-2">
                            <label class="sr-only" for="pmin">Minimum price</label>
                            <input id="pmin" inputmode="decimal" wire:model.live.debounce.600ms="priceMin" placeholder="Min" class="input">
                            <span class="text-muted">–</span>
                            <label class="sr-only" for="pmax">Maximum price</label>
                            <input id="pmax" inputmode="decimal" wire:model.live.debounce.600ms="priceMax" placeholder="Max" class="input">
                        </div>
                        <p class="field-help">Applies to products with list prices you can see.</p>
                    </fieldset>
                @endif
                <button type="button" wire:click="clearFilters" class="link text-xs">Clear all filters</button>
            </div>
        </aside>

        <div wire:loading.class="opacity-60" class="transition-opacity">
            @if ($products->isEmpty())
                <x-empty title="No products match these filters">Try removing a filter or searching for a SKU. You can also <a href="{{ route('quote.basket') }}" class="link">send us a general wholesale inquiry</a>.</x-empty>
            @elseif ($view === 'list')
                @if ($bulkMessage)<div class="mb-4 badge badge-success w-full justify-start px-4 py-3 text-sm" role="status">{{ $bulkMessage }}</div>@endif
                <div class="card overflow-x-auto">
                    <table class="table">
                        <thead><tr><th class="w-10"><span class="sr-only">Select</span></th><th>Product</th><th>Option</th><th>MOQ</th><th class="w-32">Quantity</th><th>Price</th></tr></thead>
                        <tbody>
                        @foreach ($products as $product)
                            @php $price = app(\App\Services\CatalogPricing::class)->summary($product, $access); $img = $product->primaryImage(); @endphp
                            <tr wire:key="row-{{ $product->id }}">
                                <td>
                                    @if ($product->quote_enabled)
                                        <input type="checkbox" class="checkbox mt-1" wire:model="selected.{{ $product->id }}" aria-label="Select {{ $product->name }}">
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($img)<span class="relative block shrink-0 overflow-hidden rounded-sm"><img src="{{ $img->url('thumb') }}" alt="" class="protected-img h-14 w-11 object-cover" draggable="false"><x-image-watermark size="xs"/></span>@endif
                                        <div>
                                            <a href="{{ route('products.show', $product) }}" class="font-medium hover:text-accent">{{ $product->name }}</a>
                                            <p class="text-xs text-muted">{{ $product->sku }}</p>
                                            @isset($bulkErrors[$product->id])<p class="field-error">{{ $bulkErrors[$product->id] }}</p>@endisset
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($product->activeVariants->isNotEmpty())
                                        <label class="sr-only" for="v-{{ $product->id }}">Option for {{ $product->name }}</label>
                                        <select id="v-{{ $product->id }}" wire:model="variants.{{ $product->id }}" class="input py-1.5">
                                            <option value="">Choose…</option>
                                            @foreach ($product->activeVariants as $variant)<option value="{{ $variant->id }}">{{ $variant->name }}</option>@endforeach
                                        </select>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                <td>{{ $product->moq }}@if ($product->order_multiple > 1)<span class="block text-xs text-muted">× {{ $product->order_multiple }}</span>@endif</td>
                                <td>
                                    <label class="sr-only" for="q-{{ $product->id }}">Quantity for {{ $product->name }}</label>
                                    <input id="q-{{ $product->id }}" type="number" min="{{ $product->order_multiple }}" step="{{ $product->order_multiple }}" wire:model="quantities.{{ $product->id }}" placeholder="{{ $product->moq }}" class="input py-1.5" @disabled(! $product->quote_enabled)>
                                </td>
                                <td class="whitespace-nowrap">@if ($price['cents'] !== null){{ $price['label'] }} {{ money($price['cents']) }}@else{{ $price['label'] }}@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" wire:click="addSelected" class="btn btn-primary" wire:loading.attr="disabled"><x-icon name="plus" class="h-4 w-4"/> Add selected to quote list</button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-5 gap-y-12 xl:grid-cols-3">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" wire:key="card-{{ $product->id }}"/>
                    @endforeach
                </div>
            @endif
            <div class="mt-12">{{ $products->links() }}</div>
        </div>
    </div>
</div>
</div>
