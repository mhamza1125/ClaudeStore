<div>
    <x-page-header title="B2B settings" eyebrow="Price access & image protection">
        <x-slot:actions>
            <a href="{{ route('admin.customer-accounts', ['status' => 'pending']) }}" class="btn btn-secondary btn-sm">
                Customer accounts @if ($pending)<x-badge tone="warning" class="ml-1">{{ $pending }} pending</x-badge>@endif
            </a>
        </x-slot:actions>
    </x-page-header>
    @if (session('status'))<div class="mb-4 badge badge-success w-full justify-start px-4 py-3 text-sm">{{ session('status') }}</div>@endif

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-2">
        <section class="panel space-y-5 xl:col-span-2" aria-labelledby="b2b-h">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 id="b2b-h" class="text-2xl">Wholesale price access</h2>
                    <p class="mt-1 max-w-3xl text-sm text-muted">When B2B pricing is on, these rules decide who sees catalogue prices. They are enforced on the server: hidden prices never appear in page source, search suggestions, structured data, sorting or the chatbot. Customer-specific group and negotiated prices still apply once a customer has access.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" class="checkbox" wire:model.live="s.enabled" data-testid="b2b-enabled"> Enable B2B pricing</label>
            </div>

            <div @class(['grid gap-6 lg:grid-cols-2', 'pointer-events-none opacity-50' => ! $s['enabled']]) @if (! $s['enabled']) aria-disabled="true" @endif>
                <fieldset class="space-y-3">
                    <legend class="field-label">Guest visitors</legend>
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="checkbox mt-0.5" wire:model.live="s.require_registration">
                        <span><strong>Require customer registration to view prices</strong><span class="block text-muted">Guests see “Sign in for price” and can still browse every product.</span></span></label>
                    <label @class(['flex items-start gap-2 text-sm', 'opacity-50' => $s['require_registration']])><input type="checkbox" class="checkbox mt-0.5" wire:model.live="s.guest_prices" @disabled($s['require_registration'])>
                        <span><strong>Show prices to guest visitors</strong><span class="block text-muted">Public list prices only (products marked “trade only” stay hidden). Not used while registration is required.</span></span></label>
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="checkbox mt-0.5" wire:model.live="s.guest_inquiries">
                        <span><strong>Allow guests to submit product inquiries</strong><span class="block text-muted">Quote requests without an account. Off: guests can build a quote list but must sign in to send it.</span></span></label>
                </fieldset>

                <fieldset @class(['space-y-3', 'opacity-50' => ! $s['require_registration']])>
                    <legend class="field-label">Registered customers — access method</legend>
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="checkbox mt-0.5" wire:model.live="s.require_email_verification" @disabled(! $s['require_registration'])>
                        <span><strong>Require email verification to view prices</strong><span class="block text-muted">Customers get a secure, expiring link by email and can request a new one.</span></span></label>
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="checkbox mt-0.5" wire:model.live="s.require_approval" @disabled(! $s['require_registration'])>
                        <span><strong>Require manual admin approval</strong><span class="block text-muted">New registrations wait in <a href="{{ route('admin.customer-accounts', ['status' => 'pending']) }}" class="link">Customer accounts</a>. Accounts created while this is off are approved automatically.</span></span></label>
                    <div class="rounded-sm border border-border bg-elevated px-4 py-3 text-sm" data-testid="access-method">
                        <span class="text-muted">Access method:</span> <strong>{{ $method }}</strong>
                        <span class="mt-1 block text-xs text-muted">
                            @switch ($method)
                                @case(\App\Pricing\PriceAccess::METHODS['verify']) Prices appear right after the customer verifies their email. @break
                                @case(\App\Pricing\PriceAccess::METHODS['approval']) Prices appear after an administrator approves the account. @break
                                @case(\App\Pricing\PriceAccess::METHODS['verify_approval']) Both a verified email and administrator approval are needed. @break
                                @default Prices appear immediately after registration.
                            @endswitch
                        </span>
                    </div>
                </fieldset>
            </div>
            <p class="text-xs text-muted">Always applied, whether or not B2B pricing is on: suspended accounts and customers set to “Never show prices” see no prices; customers set to “Always show prices” skip verification and approval. Staff always see prices.</p>
        </section>

        <section class="panel space-y-4" aria-labelledby="wm-h">
            <h2 id="wm-h" class="text-2xl">Image watermarking</h2>
            <p class="text-sm text-muted">One subtle mark is centred on every product image: galleries, cards, thumbnails, zoom, search and the homepage banner. Images are served as resized copies through signed links. Your original files are never changed.</p>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model.live="s.watermark_enabled"> Enable company watermark</label>
            <fieldset class="space-y-2">
                <legend class="field-label">How the watermark is applied</legend>
                <label class="flex items-start gap-2 text-sm"><input type="radio" class="mt-0.5" value="embedded" wire:model.live="s.watermark_mode">
                    <span><strong>In the served image (recommended)</strong><span class="block text-muted">Drawn into the resized copy visitors receive, so it stays on downloaded or shared files.</span></span></label>
                <label class="flex items-start gap-2 text-sm"><input type="radio" class="mt-0.5" value="overlay" wire:model.live="s.watermark_mode">
                    <span><strong>On-page overlay</strong><span class="block text-muted">Images are served unmarked and the mark is shown on top of them on the website. A deterrent only: a saved image file has no watermark.</span></span></label>
                @error('s.watermark_mode')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>
            <x-field name="s.watermark_text" model="s.watermark_text" label="Watermark text" :placeholder="$brandName" help="Leave empty to use the business name."/>
            <div>
                <label for="wm-opacity" class="field-label">Opacity: {{ $s['watermark_opacity'] }}%</label>
                <input id="wm-opacity" type="range" min="5" max="60" step="1" wire:model.live="s.watermark_opacity" class="w-full accent-[var(--color-accent)]">
                <p class="field-help">Around 15–25% stays subtle while remaining hard to remove.</p>
                @error('s.watermark_opacity')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="panel space-y-4" aria-labelledby="pwm-h">
            <h2 id="pwm-h" class="text-2xl">Personalised watermark</h2>
            <p class="text-sm text-muted">Adds the signed-in customer’s identifier in small type under the company mark, so a leaked image can be traced to the account that viewed it. Guests see the company watermark only.</p>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model.live="s.watermark_personalized"> Enable personalised watermark</label>
            <x-select-field name="s.watermark_identifier" model="s.watermark_identifier" label="Identifier" :options="['email' => 'Customer email address', 'id' => 'Customer ID (e.g. Customer #128)']" help="An ID avoids printing personal data on images."/>
            @php $sample = $s['watermark_identifier'] === 'id' ? 'Customer #128' : 'buyer@example.com'; $company = trim($s['watermark_text']) ?: $brandName; @endphp
            <div class="relative mx-auto aspect-[4/5] w-40 overflow-hidden rounded-sm" style="background: linear-gradient(160deg, #e9dcc6, #8b6a4a)" aria-label="Watermark preview" role="img">
                @if ($s['watermark_enabled'] || $s['watermark_personalized'])
                    <span class="wm-overlay wm-sm" style="opacity: {{ $s['watermark_opacity'] / 100 }}">
                        @if ($s['watermark_enabled'])<span>{{ $company }}</span>@endif
                        @if ($s['watermark_personalized'])<span class="wm-personal">{{ $sample }}</span>@endif
                    </span>
                @endif
            </div>
        </section>

        <div class="flex items-center gap-3 xl:col-span-2">
            <button type="submit" class="btn btn-primary">Save B2B settings</button>
            <span wire:loading wire:target="save" class="text-sm text-muted">Saving…</span>
        </div>
    </form>
</div>
