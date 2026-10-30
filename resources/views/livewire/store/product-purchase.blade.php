<div class="grid gap-10 lg:grid-cols-12">
    {{-- Gallery --}}
    <div class="lg:col-span-7" wire:key="gallery-{{ $variant?->id ?? 'all' }}">
        @if ($images->isNotEmpty())
            {{-- Images come from the protected media route; the centred watermark is either in the
                 served copy or laid over it by <x-image-watermark> (App\Images\ProtectedImages::mode()). --}}
            <div x-data="gallery({{ $images->count() }})" @keydown.arrow-right.prevent="go(active + 1)" @keydown.arrow-left.prevent="go(active - 1)" @keydown.escape.window="zoom = false" class="flex flex-col-reverse gap-4 sm:flex-row" data-protected>
                <div class="flex gap-3 overflow-x-auto sm:w-20 sm:flex-col" role="tablist" aria-label="Product images">
                    @foreach ($images as $i => $img)
                        <button type="button" role="tab" :aria-selected="active === {{ $i }}" @click="go({{ $i }})"
                                class="relative shrink-0 overflow-hidden rounded-sm border-2 transition" :class="active === {{ $i }} ? 'border-accent' : 'border-transparent opacity-70 hover:opacity-100'">
                            <img src="{{ $img->url('thumb') }}" alt="{{ $img->view_type?->label() ?? 'Image '.($i + 1) }}" class="protected-img h-20 w-16 object-cover" loading="lazy" draggable="false">
                            <x-image-watermark size="xs"/>
                        </button>
                    @endforeach
                </div>
                <div class="relative flex-1 overflow-hidden rounded-sm bg-elevated" tabindex="0" aria-roledescription="carousel" aria-label="Product images, use arrow keys">
                    @foreach ($images as $i => $img)
                        <figure x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif>
                            <img src="{{ $img->url('card') }}" srcset="{{ $img->url('card') }} 800w, {{ $img->url('large') }} 1600w" sizes="(min-width: 1024px) 50vw, 100vw"
                                 alt="{{ $img->alt ?: $product->name.($img->view_type ? ' — '.$img->view_type->label() : '') }}" class="protected-img aspect-[4/5] w-full cursor-zoom-in object-cover" draggable="false"
                                 @click="open({{ $i }})" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif width="800" height="1000">
                            @if ($img->view_type)<figcaption class="absolute bottom-3 left-3"><x-badge>{{ $img->view_type->label() }}</x-badge></figcaption>@endif
                        </figure>
                    @endforeach
                    <x-image-watermark size="lg"/>
                    <button type="button" @click="open(active)" class="absolute right-3 top-3 rounded-full bg-surface/90 p-2 shadow-sm hover:text-accent" aria-label="Zoom image"><x-icon name="search" class="h-4 w-4"/></button>
                </div>

                {{-- Zoom view: the large watermarked rendition, loaded only when opened. --}}
                <template x-teleport="body">
                    <div x-show="zoom" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-overlay p-4" role="dialog" aria-modal="true" aria-label="Zoomed product image" @click.self="zoom = false" data-protected>
                        @foreach ($images as $i => $img)
                            <div x-show="zoom && active === {{ $i }}" class="relative">
                                <img :src="zoom && active === {{ $i }} ? @js($img->url('large')) : ''" alt="{{ $img->alt ?: $product->name }}"
                                     class="protected-img max-h-[90vh] max-w-full rounded-sm object-contain shadow-xl" draggable="false">
                                <x-image-watermark size="lg"/>
                            </div>
                        @endforeach
                        <button type="button" @click="zoom = false" class="absolute right-4 top-4 rounded-full bg-surface p-2 shadow" aria-label="Close zoom"><x-icon name="x" class="h-5 w-5"/></button>
                        @if ($images->count() > 1)
                            <button type="button" @click="go(active - 1)" class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-surface p-2 shadow" aria-label="Previous image"><x-icon name="chevron-left" class="h-5 w-5"/></button>
                            <button type="button" @click="go(active + 1)" class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-surface p-2 shadow" aria-label="Next image"><x-icon name="chevron-right" class="h-5 w-5"/></button>
                        @endif
                    </div>
                </template>
            </div>
        @else
            <div class="flex aspect-[4/5] items-center justify-center rounded-sm bg-elevated text-muted"><x-icon name="image" class="h-12 w-12"/><span class="sr-only">No image available</span></div>
        @endif
    </div>

    {{-- Purchase panel --}}
    <div class="lg:col-span-5">
        <div class="lg:sticky lg:top-28">
            <div class="flex flex-wrap items-center gap-2">
                <x-badge :tone="($variant?->effectiveAvailability() ?? $product->availability)->tone()">{{ ($variant?->effectiveAvailability() ?? $product->availability)->label() }}</x-badge>
                @if ($product->is_custom)<x-badge tone="accent">Custom / made to order</x-badge>@endif
                @if ($product->is_demo)<x-badge>Demo product</x-badge>@endif
            </div>
            <h1 class="mt-3 text-4xl leading-tight sm:text-5xl">{{ $product->name }}</h1>
            <p class="mt-2 text-sm text-muted">SKU <span class="font-medium text-text" data-testid="sku">{{ $variant?->sku ?? $product->sku }}</span>@if ($product->product_code) · Code {{ $product->product_code }}@endif</p>
            @if ($product->short_description)<p class="mt-5 text-muted">{{ $product->short_description }}</p>@endif

            <form wire:submit="addToQuote" class="mt-8 space-y-6">
                @if ($product->activeVariants->isNotEmpty())
                    <fieldset>
                        <legend class="field-label">Option <span class="font-normal text-muted">({{ $product->activeVariants->count() }} available)</span></legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($product->activeVariants as $v)
                                <label class="cursor-pointer">
                                    <input type="radio" class="peer sr-only" wire:model.live="variantId" value="{{ $v->id }}" name="variant">
                                    <span class="inline-block rounded-sm border border-border-strong px-3 py-2 text-sm transition peer-checked:border-accent peer-checked:bg-accent-soft peer-checked:text-accent peer-focus-visible:outline-2 peer-focus-visible:outline-focus">{{ $v->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('variant')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
                @endif

                {{-- Live price --}}
                <div class="rounded-sm border border-border bg-surface p-5" aria-live="polite">
                    @if ($price->source === \App\Pricing\PriceSource::OnRequest)
                        <p class="font-serif text-3xl">Price on request</p>
                        <p class="mt-1 text-sm text-muted">Add to your quote list with the quantity and specification you need — we will quote within one business day.</p>
                    @elseif (! $access->allowed)
                        <x-price-locked :access="$access"/>
                    @elseif (! $price->visible)
                        <p class="font-serif text-3xl">Trade pricing</p>
                        <p class="mt-1 text-sm text-muted">
                            @auth Wholesale prices for this product are shown once your trade account is approved. <a href="{{ route('account.company') }}" class="link">Request access</a>.
                            @else <a href="{{ route('login') }}" class="link">Sign in</a> or <a href="{{ route('register') }}" class="link">open a trade account</a> to see wholesale prices. You can still request a quote. @endauth
                        </p>
                    @elseif ($price->hasPrice())
                        <div class="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-muted">Unit price</p>
                                <p class="font-serif text-4xl" data-testid="unit-price">{{ money($price->unitCents) }}</p>
                                <p class="mt-1 text-xs text-accent" data-testid="price-source">{{ $price->source->label() }}@if ($price->groupDiscountBps) · includes {{ \App\Support\Money::bpsToPercentString($price->groupDiscountBps) }}% account discount @endif</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs uppercase tracking-wider text-muted">Line total</p>
                                <p class="font-serif text-2xl" data-testid="line-total">{{ $price->isValidQuantity() ? money($price->lineTotalCents()) : '—' }}</p>
                            </div>
                        </div>
                        @if ($next = $price->nextTier())
                            <p class="mt-3 border-t border-border pt-3 text-xs text-muted">Order {{ $next['min'] - (int) $quantity }} more ({{ $next['min'] }}+) for {{ money($next['unit_cents']) }} per unit.</p>
                        @endif
                    @else
                        <p class="font-serif text-3xl">Price on request</p>
                    @endif
                </div>

                @if ($price->visible && count($price->ladder) > 1)
                    <table class="table text-sm" aria-label="Quantity price breaks">
                        <thead><tr><th>Quantity</th><th class="text-right">Unit price</th></tr></thead>
                        <tbody>
                        @foreach ($price->ladder as $row)
                            <tr class="{{ $row['active'] && ! $price->source->isCustomerSpecific() ? 'font-semibold text-accent' : '' }}" @if ($row['active']) aria-current="true" @endif>
                                <td>{{ number_format($row['min']) }}{{ $row['max'] ? '–'.number_format($row['max']) : '+' }} units</td>
                                <td class="text-right">{{ money($row['unit_cents']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif

                <div>
                    <label for="qty" class="field-label">Quantity <span class="font-normal text-muted">· MOQ {{ $price->moq }}@if ($price->orderMultiple > 1), multiples of {{ $price->orderMultiple }}@endif</span></label>
                    <div class="flex items-stretch gap-2">
                        <button type="button" wire:click="increment(-1)" class="btn btn-secondary !px-3" aria-label="Decrease quantity"><x-icon name="minus" class="h-4 w-4"/></button>
                        <input id="qty" type="number" inputmode="numeric" min="{{ $price->orderMultiple }}" step="{{ $price->orderMultiple }}" wire:model.live.debounce.300ms="quantity" class="input w-28 text-center text-base" aria-describedby="qty-help">
                        <button type="button" wire:click="increment(1)" class="btn btn-secondary !px-3" aria-label="Increase quantity"><x-icon name="plus" class="h-4 w-4"/></button>
                    </div>
                    <p id="qty-help" class="sr-only">Prices update automatically when the quantity changes.</p>
                    @foreach ($price->errors as $err)<p class="field-error">{{ $err }}</p>@endforeach
                    @error('quantity')@if (! in_array($message, $price->errors))<p class="field-error">{{ $message }}</p>@endif @enderror
                </div>

                @if ($product->customizable || $product->is_custom)
                    <div>
                        <button type="button" wire:click="$toggle('showCustomization')" class="link text-sm" aria-expanded="{{ $showCustomization ? 'true' : 'false' }}"><x-icon name="scissors" class="inline h-4 w-4"/> Request customisation</button>
                        @if ($showCustomization)
                            <div class="mt-3 space-y-3">
                                <x-textarea-field name="customization" model="customization" label="Customisation details" rows="3" placeholder="e.g. blind-embossed logo, 30 mm, front centre; custom colour"/>
                                <x-textarea-field name="notes" model="notes" label="Notes" rows="2"/>
                                <p class="field-help">You can attach artwork when you submit the quote request.</p>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($product->quote_enabled && $product->isPublished())
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn btn-primary btn-lg flex-1" wire:loading.attr="disabled" wire:target="addToQuote">
                            <span wire:loading.remove wire:target="addToQuote">Add to Quote</span>
                            <span wire:loading wire:target="addToQuote">Adding…</span>
                        </button>
                        @if ($added)
                            <a href="{{ route('quote.basket') }}" class="btn btn-secondary btn-lg">View quote list</a>
                        @endif
                    </div>
                    @if ($added)<p class="text-sm text-success" role="status">{{ $added }}</p>@endif
                @else
                    <p class="text-sm text-muted">This product is not currently available for quotation.</p>
                @endif
            </form>

            <div class="mt-6 flex items-center gap-4 text-sm">
                @auth
                    <form method="POST" action="{{ $isFavorite ? route('account.favorites.destroy', $product) : route('account.favorites.store', $product) }}">
                        @csrf @if ($isFavorite) @method('DELETE') @endif
                        <button class="inline-flex items-center gap-1.5 hover:text-accent"><x-icon name="heart" class="h-4 w-4 {{ $isFavorite ? 'fill-current text-accent' : '' }}"/> {{ $isFavorite ? 'Saved' : 'Save product' }}</button>
                    </form>
                @endauth
                @if ($product->lead_time)<span class="text-muted">· {{ $product->lead_time }}</span>@endif
            </div>
            <p class="mt-4 text-xs text-muted">Indicative prices in {{ config('store.currency') }}, excluding tax and shipping. Final prices are confirmed in your formal quotation.</p>
        </div>
    </div>
</div>
