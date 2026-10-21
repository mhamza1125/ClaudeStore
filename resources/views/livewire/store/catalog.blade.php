<div class="container-page py-12">
    <nav class="mb-4 text-xs text-muted" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-text">Home</a> /
        <a href="{{ route('catalog') }}" class="hover:text-text">Products</a>
        @if ($category?->parent) / <a href="{{ route('catalog.category', $category->parent) }}" class="hover:text-text">{{ $category->parent->name }}</a>@endif
        @if ($category) / <span class="text-text">{{ $category->name }}</span>@endif
    </nav>
    <x-page-header :title="$category?->name ?? 'All products'" :eyebrow="$category ? 'Collection' : 'Catalogue'">
        <x-slot:actions>
            <label for="sort" class="sr-only">Sort by</label>
            <select id="sort" wire:model.live="sort" class="input w-auto">
                <option value="featured">Featured</option>
                <option value="newest">Newest</option>
                <option value="name">Name A–Z</option>
                <option value="price_asc">Price: low to high</option>
                <option value="price_desc">Price: high to low</option>
            </select>
            <div class="flex rounded-sm border border-border-strong" role="group" aria-label="Layout">
                <button type="button" wire:click="$set('view', 'grid')" class="btn btn-sm border-0 {{ $view === 'grid' ? 'bg-elevated' : '' }}" aria-pressed="{{ $view === 'grid' ? 'true' : 'false' }}"><x-icon name="grid" class="h-4 w-4"/><span class="sr-only">Grid view</span></button>
                <button type="button" wire:click="$set('view', 'list')" class="btn btn-sm border-0 {{ $view === 'list' ? 'bg-elevated' : '' }}" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"><x-icon name="list" class="h-4 w-4"/><span class="sr-only">List view with bulk quantity entry</span></button>
            </div>
        </x-slot:actions>
    </x-page-header>
    @if ($category?->description)<p class="-mt-4 mb-8 max-w-2xl text-muted">{{ $category->description }}</p>@endif

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
                    <ul class="space-y-1.5">
                        <li><a href="{{ route('catalog') }}" class="{{ ! $category ? 'font-semibold text-accent' : 'hover:text-accent' }}">All products</a></li>
                        @foreach ($categories as $cat)
                            <li>
                                <a href="{{ route('catalog.category', $cat) }}" class="{{ $category?->id === $cat->id ? 'font-semibold text-accent' : 'hover:text-accent' }}">{{ $cat->name }}</a>
                                @if ($cat->children->isNotEmpty())
                                    <ul class="mt-1 space-y-1 border-l border-border pl-3">
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
                <fieldset>
                    <legend class="eyebrow mb-3">List price (USD)</legend>
                    <div class="flex items-center gap-2">
                        <label class="sr-only" for="pmin">Minimum price</label>
                        <input id="pmin" inputmode="decimal" wire:model.live.debounce.600ms="priceMin" placeholder="Min" class="input">
                        <span class="text-muted">–</span>
                        <label class="sr-only" for="pmax">Maximum price</label>
                        <input id="pmax" inputmode="decimal" wire:model.live.debounce.600ms="priceMax" placeholder="Max" class="input">
                    </div>
                    <p class="field-help">Applies to products with public list prices.</p>
                </fieldset>
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
                            @php $price = app(\App\Services\CatalogPricing::class)->summary($product, auth()->user()?->company); $img = $product->primaryImage(); @endphp
                            <tr wire:key="row-{{ $product->id }}">
                                <td>
                                    @if ($product->quote_enabled)
                                        <input type="checkbox" class="checkbox mt-1" wire:model="selected.{{ $product->id }}" aria-label="Select {{ $product->name }}">
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($img)<img src="{{ $img->url() }}" alt="" class="h-14 w-11 rounded-sm object-cover">@endif
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
                                    <input id="q-{{ $product->id }}" type="number" min="1" step="{{ $product->order_multiple }}" wire:model="quantities.{{ $product->id }}" placeholder="{{ $product->moq }}" class="input py-1.5" @disabled(! $product->quote_enabled)>
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
