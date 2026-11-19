<div x-data="{ filters: false }" @keydown.escape.window="filters = false">
    @php
        // Header links: every main collection (current one highlighted), then the subcategories
        // of the current collection.
        $currentRoot = $category?->parent ?? $category;
        $subcategories = $currentRoot ? ($categories->firstWhere('id', $currentRoot->id)?->children ?? collect()) : collect();
        $description = $category?->description ?: $category?->parent?->description ?: ($raw ? 'Hides, sides and skins priced by quotation. Add the leathers you need to a quote with your quantities and we will reply with pricing.' : null) ?: 'Premium leather bags, belts, wallets and accessories for retailers, distributors and brands.';
        $activeFilters = count($material) + count($color) + count($availability) + (trim($q) !== '' ? 1 : 0) + (trim($priceMin) !== '' ? 1 : 0) + (trim($priceMax) !== '' ? 1 : 0);
    @endphp
    <section class="catalog-hero bg-band text-on-band" data-testid="catalog-hero">
        <div class="container-page py-8 sm:py-12">
            <nav class="mb-5 text-xs text-on-band/70" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-on-band hover:underline">Home</a> /
                <a href="{{ route('catalog') }}" class="hover:text-on-band hover:underline">Products</a>
                @if ($category?->parent) / <a href="{{ route('catalog.category', $category->parent) }}" class="hover:text-on-band hover:underline">{{ $category->parent->name }}</a>@endif
                @if ($category) / <span class="text-on-band">{{ $category->name }}</span>@endif
            </nav>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <h1 class="text-4xl leading-tight sm:text-5xl lg:text-6xl">{{ $category?->name ?? ($raw ? 'Raw leather' : 'All products') }}</h1>
                <span class="rounded-full border border-on-band/25 px-3 py-1 text-xs tracking-wide text-on-band/80" data-testid="catalog-count">
                    {{ number_format($products->total()) }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                </span>
            </div>
            {{-- One quiet line (two on small screens); the full text stays available on hover. --}}
            <p class="mt-3 max-w-3xl text-sm leading-relaxed text-on-band/75 line-clamp-2" title="{{ $description }}">{{ $description }}</p>

            {{-- Chips scroll sideways on phones instead of wrapping into a tall block. --}}
            <div class="no-scrollbar -mx-4 mt-7 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Collections">
                <a href="{{ route('catalog') }}" class="catalog-chip {{ ! $category && ! $raw ? 'is-active' : '' }}">All products</a>
                @foreach ($categories as $root)
                    <a href="{{ route('catalog.category', $root) }}" class="catalog-chip {{ $currentRoot?->id === $root->id ? 'is-active' : '' }}" @if ($currentRoot?->id === $root->id) aria-current="true" @endif>{{ $root->name }}</a>
                @endforeach
            </div>
            @if ($subcategories->isNotEmpty())
                <div class="no-scrollbar -mx-4 mt-4 flex items-center gap-1.5 overflow-x-auto border-t border-on-band/15 px-4 pt-4 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="{{ $currentRoot->name }} subcategories">
                    <a href="{{ route('catalog.category', $currentRoot) }}" class="catalog-chip catalog-chip--sub {{ $category?->id === $currentRoot->id ? 'is-active' : '' }}">All {{ $currentRoot->name }}</a>
                    @foreach ($subcategories as $sub)
                        <a href="{{ route('catalog.category', $sub) }}" class="catalog-chip catalog-chip--sub {{ $category?->id === $sub->id ? 'is-active' : '' }}" @if ($category?->id === $sub->id) aria-current="page" @endif>{{ $sub->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Thin progress line while Livewire is working; the catalogue itself stays fully visible. --}}
    <div wire:loading.delay.shortest class="load-bar" role="status" aria-label="Updating results"></div>

    <div class="container-page py-8 lg:py-10">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
        <div class="flex items-center gap-3">
            <button type="button" class="btn btn-secondary btn-sm lg:hidden" @click="filters = true" aria-controls="catalog-filters" :aria-expanded="filters">
                <x-icon name="list" class="h-4 w-4"/> Filters
                @if ($activeFilters)<span class="inline-flex min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[11px] font-semibold leading-5 text-on-primary" data-testid="active-filter-count">{{ $activeFilters }}</span>@endif
            </button>
            <p class="text-sm text-muted">Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label for="sort" class="sr-only sm:not-sr-only sm:text-sm sm:text-muted">Sort by</label>
            <select id="sort" wire:model.live="sort" class="input w-auto py-1.5">
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

    <div class="grid gap-10 lg:grid-cols-[250px_1fr]">
        {{-- Filters: a sidebar on desktop, an off-canvas drawer below lg. --}}
        <div x-show="filters" x-cloak x-transition.opacity class="fixed inset-0 z-50 bg-overlay lg:hidden" @click="filters = false" aria-hidden="true"></div>
        <aside id="catalog-filters" aria-label="Filters"
               class="fixed inset-y-0 left-0 z-50 w-80 max-w-[88vw] -translate-x-full overflow-y-auto bg-bg p-5 shadow-xl transition-transform duration-200 lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none"
               :class="filters && '!translate-x-0'" x-trap.noscroll="filters && window.matchMedia('(max-width: 1023px)').matches">
            <div class="mb-5 flex items-center justify-between lg:hidden">
                <h2 class="text-2xl">Filters @if ($activeFilters)<span class="text-base text-muted">({{ $activeFilters }})</span>@endif</h2>
                <button type="button" class="btn btn-ghost btn-sm !px-2" @click="filters = false" aria-label="Close filters"><x-icon name="x" class="h-5 w-5"/></button>
            </div>
            <div class="space-y-1 text-sm">
                <div class="pb-5">
                    <label for="catalog-q" class="eyebrow mb-3 block">Search</label>
                    <input id="catalog-q" type="search" wire:model.live.debounce.400ms="q" placeholder="Name, SKU or code" class="input">
                </div>

                <div x-data="{ open: true }" class="border-t border-border py-5">
                    <button type="button" class="flex w-full items-center justify-between" @click="open = !open" :aria-expanded="open"><span class="eyebrow">Categories</span><x-icon name="chevron-down" class="h-4 w-4 text-muted transition-transform" x-bind:class="open || '-rotate-90'"/></button>
                    <ul x-show="open" class="mt-4 space-y-3">
                        <li><a href="{{ route('catalog') }}" class="{{ ! $category ? 'font-semibold text-accent' : 'hover:text-accent' }}">All products</a></li>
                        @foreach ($categories as $cat)
                            <li x-data="{ sub: {{ $currentRoot?->id === $cat->id ? 'true' : 'false' }} }">
                                <div class="flex items-center justify-between gap-2">
                                    <a href="{{ route('catalog.category', $cat) }}" class="{{ $category?->id === $cat->id ? 'font-semibold text-accent' : 'hover:text-accent' }}">{{ $cat->name }}</a>
                                    @if ($cat->children->isNotEmpty())
                                        <button type="button" class="p-1 text-muted" @click="sub = !sub" :aria-expanded="sub" aria-label="Show {{ $cat->name }} subcategories"><x-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" x-bind:class="sub && 'rotate-180'"/></button>
                                    @endif
                                </div>
                                @if ($cat->children->isNotEmpty())
                                    <ul x-show="sub" class="mb-1 mt-2 space-y-1.5 border-l border-border-strong pl-3">
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
                    <fieldset x-data="{ open: true }" class="border-t border-border py-5">
                        <legend class="sr-only">Material</legend>
                        <button type="button" class="flex w-full items-center justify-between" @click="open = !open" :aria-expanded="open"><span class="eyebrow">Material @if (count($material))<span class="text-text">({{ count($material) }})</span>@endif</span><x-icon name="chevron-down" class="h-4 w-4 text-muted transition-transform" x-bind:class="open || '-rotate-90'"/></button>
                        <div x-show="open" class="mt-3">
                            @foreach ($materials as $m)
                                <label class="flex cursor-pointer items-center gap-2.5 py-1.5"><input type="checkbox" class="checkbox" value="{{ $m }}" wire:model.live="material"> {{ $m }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                @if ($colors->isNotEmpty())
                    <fieldset x-data="{ open: true }" class="border-t border-border py-5">
                        <legend class="sr-only">Colour</legend>
                        <button type="button" class="flex w-full items-center justify-between" @click="open = !open" :aria-expanded="open"><span class="eyebrow">Colour @if (count($color))<span class="text-text">({{ count($color) }})</span>@endif</span><x-icon name="chevron-down" class="h-4 w-4 text-muted transition-transform" x-bind:class="open || '-rotate-90'"/></button>
                        <div x-show="open" class="mt-3">
                            @foreach ($colors as $c)
                                @php $swatch = \App\Support\LeatherColors::css($c); @endphp
                                <label class="flex cursor-pointer items-center gap-2.5 py-1.5">
                                    <input type="checkbox" class="checkbox" value="{{ $c }}" wire:model.live="color">
                                    @if ($swatch)<span class="h-4 w-4 shrink-0 rounded-full border border-border-strong" style="background: {{ $swatch }}" aria-hidden="true"></span>@endif
                                    {{ $c }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                <fieldset x-data="{ open: true }" class="border-t border-border py-5">
                    <legend class="sr-only">Availability</legend>
                    <button type="button" class="flex w-full items-center justify-between" @click="open = !open" :aria-expanded="open"><span class="eyebrow">Availability @if (count($availability))<span class="text-text">({{ count($availability) }})</span>@endif</span><x-icon name="chevron-down" class="h-4 w-4 text-muted transition-transform" x-bind:class="open || '-rotate-90'"/></button>
                    <div x-show="open" class="mt-3">
                        @foreach ($availabilities as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 py-1.5"><input type="checkbox" class="checkbox" value="{{ $value }}" wire:model.live="availability"> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                @if ($access->allowed)
                    <fieldset class="border-t border-border py-5">
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
                <div class="flex items-center justify-between gap-3 border-t border-border pt-5">
                    <button type="button" wire:click="clearFilters" class="link text-xs {{ $activeFilters ? '' : 'pointer-events-none opacity-40' }}" @if (! $activeFilters) disabled @endif>Clear all filters @if ($activeFilters)({{ $activeFilters }})@endif</button>
                    <button type="button" class="btn btn-primary btn-sm lg:hidden" @click="filters = false">Show {{ number_format($products->total()) }} results</button>
                </div>
            </div>
        </aside>

        <div>
            @if ($products->isEmpty())
                <x-empty title="No products match these filters">Try removing a filter or searching for a SKU. You can also <a href="{{ route('quote.basket') }}" class="link">send us a general wholesale inquiry</a>.</x-empty>
            @elseif ($view === 'list')
                @if ($bulkMessage)<div class="mb-4 badge badge-success w-full justify-start px-4 py-3 text-sm" role="status">{{ $bulkMessage }}</div>@endif
                <div class="card overflow-x-auto">
                    <table class="table min-w-[44rem]">
                        <thead><tr><th class="w-10"><span class="sr-only">Select</span></th><th>Product</th><th>Option</th><th>MOQ</th><th class="w-32">Quantity</th><th class="text-right">Price</th></tr></thead>
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
                                        @if ($img)<span class="relative block shrink-0 overflow-hidden rounded-sm"><img src="{{ $img->url('thumb') }}" alt="" class="protected-img h-16 w-12 object-cover" draggable="false"><x-image-watermark size="xs"/></span>@endif
                                        <div>
                                            <a href="{{ route('products.show', $product) }}" class="font-serif text-lg font-semibold leading-tight hover:text-accent">{{ $product->name }}</a>
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
                                <td class="whitespace-nowrap"><span class="font-medium">{{ $product->moq }}</span>@if ($product->order_multiple > 1)<span class="block text-xs text-muted">× {{ $product->order_multiple }}</span>@endif</td>
                                <td>
                                    <label class="sr-only" for="q-{{ $product->id }}">Quantity for {{ $product->name }}</label>
                                    <input id="q-{{ $product->id }}" type="number" min="{{ $product->order_multiple }}" step="{{ $product->order_multiple }}" wire:model="quantities.{{ $product->id }}" placeholder="{{ $product->moq }}" class="input py-1.5 text-center" @disabled(! $product->quote_enabled)>
                                </td>
                                <td class="whitespace-nowrap text-right">@if ($price['cents'] !== null)<span class="text-xs text-muted">{{ $price['label'] }}</span> <span class="font-semibold">{{ money($price['cents']) }}</span>@else{{ $price['label'] }}@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" wire:click="addSelected" class="btn btn-primary" wire:loading.attr="disabled"><x-icon name="plus" class="h-4 w-4"/> Add selected to quote list</button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 md:grid-cols-3 md:gap-y-12 xl:grid-cols-4">
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
