<div class="container-page py-12">
    <x-page-header title="Your quote list" eyebrow="Request for quotation"/>
    <x-flash/>

    <div class="grid gap-12 lg:grid-cols-12">
        <section class="lg:col-span-7" aria-labelledby="items-heading">
            <h2 id="items-heading" class="sr-only">Items</h2>
            @if ($message)<p class="mb-4 text-sm text-success" role="status">{{ $message }}</p>@endif
            @forelse ($lines as $line)
                @php $item = $line['item']; $price = $line['price']; $img = $item->product->primaryImage(); @endphp
                <article wire:key="line-{{ $item->id }}" class="flex gap-4 border-b border-border py-6 first:pt-0">
                    <a href="{{ route('products.show', $item->product) }}" class="relative shrink-0 self-start overflow-hidden rounded-sm">
                        @if ($img)<img src="{{ $img->url('thumb') }}" alt="" class="protected-img h-28 w-22 object-cover" draggable="false"><x-image-watermark size="xs"/>@else<div class="h-28 w-22 rounded-sm bg-elevated"></div>@endif
                    </a>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h3 class="font-serif text-xl"><a href="{{ route('products.show', $item->product) }}" class="hover:text-accent">{{ $item->product->name }}</a></h3>
                                <p class="text-xs text-muted">SKU {{ $item->variant?->sku ?? $item->product->sku }}@if ($item->variant) · {{ $item->variant->name }}@endif</p>
                            </div>
                            <div class="text-right text-sm">
                                @if ($price->displayUnitCents() !== null)
                                    <p><span class="text-muted">Indicative</span> {{ money($price->displayUnitCents()) }} <span class="text-muted">/ unit</span></p>
                                    <p class="text-xs text-accent">{{ $price->source->label() }}@if ($price->tierMinQty) · {{ $price->tierMinQty }}+ tier @endif</p>
                                    <p class="mt-1 font-semibold">{{ money($price->displayLineTotalCents()) }}</p>
                                @else
                                    <p class="text-muted">{{ $price->source === \App\Pricing\PriceSource::OnRequest ? 'Price on request' : 'Priced in your quotation' }}</p>
                                @endif
                            </div>
                        </div>
                        <form wire:submit="save({{ $item->id }})" class="mt-4 grid gap-3 sm:grid-cols-[120px_1fr]">
                            <div>
                                <label for="qty-{{ $item->id }}" class="field-label text-xs">Quantity</label>
                                <input id="qty-{{ $item->id }}" type="number" min="{{ $price->orderMultiple }}" step="{{ $price->orderMultiple }}" wire:model="edits.{{ $item->id }}.quantity" class="input">
                                <p class="field-help">MOQ {{ $price->moq }}@if ($price->orderMultiple > 1) · ×{{ $price->orderMultiple }}@endif</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="cust-{{ $item->id }}" class="field-label text-xs">Customisation</label>
                                    <textarea id="cust-{{ $item->id }}" rows="2" wire:model="edits.{{ $item->id }}.customization" class="input" placeholder="Embossing, colours…"></textarea>
                                </div>
                                <div>
                                    <label for="notes-{{ $item->id }}" class="field-label text-xs">Notes</label>
                                    <textarea id="notes-{{ $item->id }}" rows="2" wire:model="edits.{{ $item->id }}.notes" class="input"></textarea>
                                </div>
                            </div>
                            @error("edits.{$item->id}.quantity")<p class="field-error sm:col-span-2">{{ $message }}</p>@enderror
                            <div class="flex gap-2 sm:col-span-2">
                                <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                                <button type="button" wire:click="remove({{ $item->id }})" wire:confirm="Remove this item from your quote list?" class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="h-4 w-4"/> Remove</button>
                            </div>
                        </form>
                    </div>
                </article>
            @empty
                <x-empty title="Your quote list is empty" icon="quote">
                    Browse the <a href="{{ route('catalog') }}" class="link">catalogue</a> and use “Add to Quote”, or describe what you need in the form — general wholesale and private-label inquiries are welcome.
                </x-empty>
            @endforelse

            @if ($lines->isNotEmpty())
                <div class="mt-6 flex items-center justify-between rounded-sm bg-elevated px-5 py-4">
                    <span class="text-sm text-muted">Indicative subtotal ({{ $lines->count() }} {{ \Illuminate\Support\Str::plural('line', $lines->count()) }})</span>
                    <span class="font-serif text-2xl" data-testid="basket-total">{{ $total !== null ? money($total) : 'To be quoted' }}</span>
                </div>
                @if ($belowMinimum)
                    <p class="mt-3 text-sm text-warning" role="alert">The minimum order value is {{ money($minOrder) }}. Increase quantities or add products before submitting.</p>
                @endif
                <p class="mt-3 text-xs text-muted">Indicative prices exclude tax and shipping. Your formal quotation will confirm final pricing, which may differ.</p>
            @endif
        </section>

        <section class="lg:col-span-5" aria-labelledby="form-heading" wire:ignore>
            @if (! $user && ! $guestInquiries)
                <div class="panel" data-testid="inquiry-sign-in">
                    <h2 id="form-heading" class="text-3xl">Request your quotation</h2>
                    <p class="mt-2 text-sm text-muted">Sign in or create a free trade account to send your quote request. Your quote list is kept when you sign in.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
                        <a href="{{ route('register') }}" class="btn btn-secondary">Create an account</a>
                    </div>
                </div>
            @else
            <div class="panel">
                <h2 id="form-heading" class="text-3xl">Request your quotation</h2>
                <p class="mt-1 mb-6 text-sm text-muted">We reply within one business day. Submitting a request does not place an order.</p>
                <form method="POST" action="{{ route('quote.submit') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="form_started" value="{{ encrypt(now()->timestamp) }}">
                    <div class="hidden" aria-hidden="true"><label>Fax number<input type="text" name="fax_number" tabindex="-1" autocomplete="off"></label></div>
                    @error('items')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    @error('form')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    <x-field name="company_name" label="Company name" required :value="$company?->name" autocomplete="organization"/>
                    <x-field name="contact_name" label="Contact person" required :value="$user?->name" autocomplete="name"/>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field name="email" type="email" label="Business email" required :value="$user?->email" autocomplete="email"/>
                        <x-field name="phone" type="tel" label="Phone (with country code)" required :value="$user?->phone ?? $company?->phone" placeholder="+44 20 7946 0000" autocomplete="tel"/>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-select-field name="country" label="Country" required :options="countries()" placeholder="Select…" :value="$company?->country"/>
                        <x-field name="delivery_destination" label="Delivery destination" required placeholder="City, country / port"/>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field name="website" type="url" label="Company website" placeholder="https://" :value="$company?->website"/>
                        <x-select-field name="business_type" label="Business type" placeholder="Select…" :value="$company?->business_type" :options="array_combine(\App\Http\Requests\QuoteSubmissionRequest::BUSINESS_TYPES, \App\Http\Requests\QuoteSubmissionRequest::BUSINESS_TYPES)"/>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-field name="expected_volume" label="Expected volume" placeholder="e.g. 500 units / quarter" class="sm:col-span-2"/>
                        <x-select-field name="preferred_currency" label="Currency" :options="array_combine(config('store.display_currencies'), config('store.display_currencies'))" value="USD" help="Quotes are issued in USD."/>
                    </div>
                    <x-field name="target_delivery_date" type="date" label="Target delivery date" :min="now()->addDay()->toDateString()"/>
                    <x-textarea-field name="specifications" label="Required product specifications" rows="3" placeholder="Materials, colours, sizes, branding, packaging…"/>
                    <x-textarea-field name="message" label="Message" rows="3"/>
                    <div>
                        <label for="attachments" class="field-label">Attachments</label>
                        <input id="attachments" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" class="input file:mr-3 file:border-0 file:bg-elevated file:px-3 file:py-1 file:text-sm">
                        <p class="field-help">Artwork, reference photos or specs. Up to 5 files, 10 MB each (PDF, JPG, PNG, WebP).</p>
                        @error('attachments')<p class="field-error">{{ $message }}</p>@enderror
                        @error('attachments.*')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    @guest
                        <p class="text-xs text-muted">Have a trade account? <a href="{{ route('login') }}" class="link">Sign in</a> to save this quote to your history and see your account pricing.</p>
                    @endguest
                    <button type="submit" class="btn btn-primary btn-lg w-full">Submit quote request</button>
                    <p class="text-xs text-muted">By submitting you agree that we may contact you about this request. See our <a class="link" href="{{ route('pages.show', 'privacy') }}">privacy policy</a>.</p>
                </form>
            </div>
            @endif
        </section>
    </div>
</div>
