<div>
    <section class="border-b border-border bg-elevated">
        <div class="container-page pb-10 pt-8 sm:pt-10">
            <x-breadcrumbs :items="[['Home', route('home')], ['Quote list', route('quote.basket')]]" class="mb-8"/>
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="eyebrow eyebrow-stitch mb-4">Request for quotation</p>
                    <h1 class="h-section">Your quote list</h1>
                </div>
                {{-- The three steps of an RFQ, so buyers know a request is not an order. --}}
                <ol class="flex flex-wrap items-center gap-2 text-[13px]" aria-label="How quoting works">
                    @foreach (['Build your list', 'Send your request', 'Receive a formal quotation'] as $i => $step)
                        <li class="flex items-center gap-2"><span @class(['flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold', 'bg-primary text-on-primary' => $i === 0, 'border border-border-strong bg-surface text-muted' => $i > 0])>{{ $i + 1 }}</span><span class="{{ $i === 0 ? 'font-medium' : 'text-muted' }}">{{ $step }}</span>@unless ($loop->last)<span class="stitch w-6" aria-hidden="true"></span>@endunless</li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>
    <div class="container-page py-10 lg:py-14">
    <x-flash/>

    <div class="grid gap-10 lg:grid-cols-12 lg:gap-12">
        <section class="lg:col-span-7" aria-labelledby="items-heading">
            <h2 id="items-heading" class="sr-only">Items</h2>
            @if ($message)<div class="alert badge-success mb-4" role="status"><x-icon name="check" class="h-4 w-4 shrink-0"/>{{ $message }}</div>@endif
            @forelse ($lines as $line)
                @php $item = $line['item']; $price = $line['price']; $img = $item->product->primaryImage(); @endphp
                <article wire:key="line-{{ $item->id }}" class="card mb-4 flex gap-4 p-4 sm:gap-5 sm:p-5">
                    <a href="{{ route('products.show', $item->product) }}" class="relative shrink-0 self-start overflow-hidden rounded-md bg-elevated">
                        @if ($img)<img src="{{ $img->url('thumb') }}" alt="" class="protected-img h-28 w-22 object-cover" draggable="false"><x-image-watermark size="xs"/>@else<div class="h-28 w-22 rounded-md bg-elevated"></div>@endif
                    </a>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h3 class="font-serif text-xl"><a href="{{ route('products.show', $item->product) }}" class="hover:text-accent">{{ $item->product->name }}</a></h3>
                                <p class="mt-0.5 text-xs text-muted tabular-nums">SKU {{ $item->variant?->sku ?? $item->product->sku }}@if ($item->variant) · {{ $item->variant->name }}@endif</p>
                                @if ($item->variant && ! $item->variant->is_active)<p class="mt-1 text-xs font-medium text-danger">This option is no longer available. Please remove it.</p>@endif
                            </div>
                            <div class="text-right text-sm">
                                @if ($price->displayUnitCents() !== null)
                                    <p><span class="text-muted">Indicative</span> {{ money($price->displayUnitCents()) }} <span class="text-muted">/ unit</span></p>
                                    <p class="text-xs text-accent">{{ $price->source->label() }}@if ($price->tierMinQty) · {{ $price->tierMinQty }}+ tier @endif</p>
                                    <p class="mt-1 font-semibold tabular-nums">{{ money($price->displayLineTotalCents()) }}</p>
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
                <div class="mt-6 flex items-center justify-between rounded-lg bg-band px-6 py-5 text-on-band">
                    <span class="text-sm text-on-band/75">Indicative subtotal ({{ $lines->count() }} {{ \Illuminate\Support\Str::plural('line', $lines->count()) }})</span>
                    <span class="font-serif text-3xl tabular-nums" data-testid="basket-total">{{ $total !== null ? money($total) : 'To be quoted' }}</span>
                </div>
                @if ($belowMinimum)
                    <p class="alert badge-warning mt-3" role="alert">The minimum order value is {{ money($minOrder) }}. Increase quantities or add products before submitting.</p>
                @endif
                <p class="mt-3 text-xs text-muted">Indicative prices exclude tax and shipping. Your formal quotation will confirm final pricing, which may differ.</p>
            @endif
        </section>

        <section class="lg:col-span-5" aria-labelledby="form-heading" wire:ignore>
            @if (! $user && ! $guestInquiries)
                <div class="panel shadow-sm lg:sticky lg:top-28" data-testid="inquiry-sign-in">
                    <h2 id="form-heading" class="h-product">Request your quotation</h2>
                    <p class="mt-2 text-sm text-muted">Sign in or create a free trade account to send your quote request. Your quote list is kept when you sign in.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
                        <a href="{{ route('register') }}" class="btn btn-secondary">Create an account</a>
                    </div>
                </div>
            @else
            <div class="panel shadow-sm sm:p-8">
                <h2 id="form-heading" class="h-product">Request your quotation</h2>
                <p class="mt-1 mb-6 text-sm text-muted">We reply within one business day. Submitting a request does not place an order.</p>
                <form method="POST" action="{{ route('quote.submit') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="form_started" value="{{ encrypt(now()->timestamp) }}">
                    <div class="hidden" aria-hidden="true"><label>Fax number<input type="text" name="fax_number" tabindex="-1" autocomplete="off"></label></div>
                    @error('items')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    @error('form')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    <fieldset class="space-y-4">
                    <legend class="mb-2 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-band font-serif text-sm text-on-band">01</span><span class="text-base font-semibold">Business information</span></legend>
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
                    </fieldset>
                    <fieldset class="space-y-4 border-t border-border pt-6">
                    <legend class="mb-2 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-band font-serif text-sm text-on-band">02</span><span class="text-base font-semibold">Order requirements</span></legend>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-field name="expected_volume" label="Expected volume" placeholder="e.g. 500 units / quarter" class="sm:col-span-2"/>
                        <x-select-field name="preferred_currency" label="Currency" :options="array_combine(config('store.display_currencies'), config('store.display_currencies'))" value="USD" help="Quotes are issued in USD."/>
                    </div>
                    <x-field name="target_delivery_date" type="date" label="Target delivery date" :min="now()->addDay()->toDateString()"/>
                    <x-textarea-field name="specifications" label="Required product specifications" rows="3" placeholder="Materials, colours, sizes, branding, packaging…"/>
                    <x-textarea-field name="message" label="Message" rows="3"/>
                    </fieldset>
                    <fieldset class="space-y-4 border-t border-border pt-6">
                    <legend class="mb-2 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-band font-serif text-sm text-on-band">03</span><span class="text-base font-semibold">Attachments</span></legend>
                    <div>
                        <label for="attachments" class="field-label">Technical documents, reference images or design files</label>
                        <input id="attachments" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" class="input file:mr-3 file:cursor-pointer file:rounded-full file:border-0 file:bg-elevated file:px-3.5 file:py-1 file:text-sm file:font-medium">
                        <p class="field-help">Artwork, reference photos or specs. Up to 5 files, 10 MB each (PDF, JPG, PNG, WebP).</p>
                        @error('attachments')<p class="field-error">{{ $message }}</p>@enderror
                        @error('attachments.*')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    </fieldset>
                    @guest
                        <p class="text-xs text-muted">Have a trade account? <a href="{{ route('login') }}" class="link">Sign in</a> to save this quote to your history and see your account pricing.</p>
                    @endguest
                    <div class="border-t border-border pt-6"><button type="submit" class="btn btn-primary btn-lg w-full">Submit quote request</button></div>
                    <p class="text-xs text-muted">By submitting you agree that we may contact you about this request. See our <a class="link" href="{{ route('pages.show', 'privacy') }}">privacy policy</a>.</p>
                </form>
            </div>
            @endif
        </section>
    </div>
    </div>
</div>
