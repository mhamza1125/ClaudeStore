<div>
    <x-page-header title="Settings" eyebrow="Store configuration"/>
    @if (session('status'))<div class="mb-4 badge badge-success w-full justify-start px-4 py-3 text-sm">{{ session('status') }}</div>@endif
    <form wire:submit="save" class="grid gap-6 xl:grid-cols-2">
        <section class="panel grid gap-4 sm:grid-cols-2">
            <h2 class="text-2xl sm:col-span-2">Brand identity</h2>
            <x-field name="s.brand_name" model="s.brand_name" label="Business name" required/>
            <x-field name="s.brand_tagline" model="s.brand_tagline" label="Tagline"/>
            <x-field name="s.brand_email" model="s.brand_email" type="email" label="Contact email" required/>
            <x-field name="s.brand_phone" model="s.brand_phone" label="Phone"/>
            <x-field name="s.brand_address" model="s.brand_address" label="Address" class="sm:col-span-2"/>
            <x-textarea-field name="s.company_intro" model="s.company_intro" label="Company introduction" rows="3" class="sm:col-span-2" help="Shown on the homepage and About page. Leave blank to use the brand description. Only state verified facts."/>
            <x-textarea-field name="s.company_story" model="s.company_story" label="Company story (optional)" rows="5" class="sm:col-span-2" help="Longer text for the About page: workshop, location, materials sourcing, certifications. Leave blank to hide."/>
            <x-field name="s.social_instagram" model="s.social_instagram" label="Instagram URL"/>
            <x-field name="s.social_linkedin" model="s.social_linkedin" label="LinkedIn URL"/>
            <x-field name="s.social_facebook" model="s.social_facebook" label="Facebook URL"/>
            <div class="sm:col-span-2">
                <label for="logo" class="field-label">Logo</label>
                @if ($logoPath)<div class="mb-2 flex items-center gap-3"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) }}" alt="Current logo" class="h-10"><button type="button" wire:click="removeLogo" class="link text-xs">Remove</button></div>@endif
                <input id="logo" type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="input">
                <p class="field-help">Without a logo, a monogram placeholder is shown. Brand colours are design tokens in resources/css/app.css.</p>
                @error('logo')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </section>
        <section class="panel grid gap-4 sm:grid-cols-2">
            <h2 class="text-2xl sm:col-span-2">Commercial defaults</h2>
            <x-field name="s.sales_email" model="s.sales_email" type="email" label="Sales notification email" help="Receives new quote requests and responses." class="sm:col-span-2"/>
            <x-field name="s.tax_rate" model="s.tax_rate" label="Flat tax rate (%)"/>
            <x-field name="s.tax_label" model="s.tax_label" label="Tax label" help="e.g. VAT, Sales tax"/>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" class="checkbox" wire:model="s.tax_on_shipping"> Apply tax to shipping</label>
            <x-field name="s.shipping_flat" model="s.shipping_flat" label="Flat shipping per quote (USD)"/>
            <x-field name="s.min_order" model="s.min_order" label="Minimum order value (USD)" help="0 = none. Checked when all lines have visible prices."/>
            <x-field name="s.validity_days" model="s.validity_days" type="number" label="Default quote validity (days)"/>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model="s.newsletter"> Enable newsletter signup</label>
            <x-textarea-field name="s.payment_terms" model="s.payment_terms" label="Default payment terms" rows="3" class="sm:col-span-2"/>
            <x-textarea-field name="s.delivery_terms" model="s.delivery_terms" label="Default delivery terms" rows="3" class="sm:col-span-2"/>
            <x-textarea-field name="s.acceptance" model="s.acceptance" label="Acceptance instructions" rows="3" class="sm:col-span-2"/>
        </section>
        <section class="panel space-y-4 xl:col-span-2" aria-labelledby="theme-h">
            <h2 id="theme-h" class="text-2xl">Appearance</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <x-select-field name="s.theme_default" model="s.theme_default" label="Default theme" :options="collect(\App\Support\Theme::CHOICES)->map(fn ($c, $k) => $c[0].($k === 'system' ? ' (follow the visitor’s device)' : ''))->all()" help="Used for new visitors and whenever switching is turned off."/>
                <x-select-field name="s.theme_system_light" model="s.theme_system_light" label="Light palette for “System”" :options="['white' => 'White', 'mid' => 'Mid']" help="Devices in light mode get this palette; dark mode gets Dark."/>
                <label class="flex items-center gap-2 pt-7 text-sm"><input type="checkbox" class="checkbox" wire:model="s.theme_allow_switch"> Let visitors switch theme</label>
            </div>
            <div class="grid gap-3 sm:grid-cols-3" aria-hidden="true">
                @foreach (['white' => ['#ffffff', '#261a14', '#a78657', '#f5f4f1'], 'mid' => ['#e6dccd', '#261a14', '#6b4a22', '#d9ccb8'], 'dark' => ['#19130f', '#f4ede3', '#c0a272', '#30231b']] as $key => [$bg, $fg, $ac, $el])
                    {{-- Fixed swatches so admins can compare palettes regardless of the admin's own theme. --}}
                    <div class="rounded-sm border border-border p-3 text-xs" style="background: {{ $bg }}; color: {{ $fg }}">
                        <p class="font-semibold">{{ \App\Support\Theme::PALETTES[$key] }}</p>
                        <p class="mt-1 flex items-center gap-2"><span class="inline-block h-4 w-8 rounded-sm" style="background: {{ $fg }}"></span><span class="inline-block h-4 w-8 rounded-sm" style="background: {{ $ac }}"></span><span class="inline-block h-4 w-8 rounded-sm border" style="background: {{ $el }}"></span></p>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="panel space-y-4" aria-labelledby="wa-h">
            <h2 id="wa-h" class="text-2xl">WhatsApp button</h2>
            <p class="text-sm text-muted">A floating button in the bottom-left corner of the storefront that opens WhatsApp with a prefilled message.</p>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model.live="s.whatsapp_enabled"> Show the WhatsApp button</label>
            <x-field name="s.whatsapp_phone" model="s.whatsapp_phone" label="WhatsApp number" placeholder="+92 311 0012233" help="International format with country code. Spaces, + and dashes are removed automatically." :required="$s['whatsapp_enabled']"/>
            <x-select-field name="s.whatsapp_preset" model="s.whatsapp_preset" label="Prefilled message" :options="$whatsappPresets"/>
            @if ($s['whatsapp_preset'] === 'custom')
                <x-textarea-field name="s.whatsapp_custom" model="s.whatsapp_custom" label="Custom message" rows="3" required/>
            @endif
            <div class="text-xs">
                <p class="field-label text-xs">Link preview</p>
                @if ($whatsappPreview)
                    <a href="{{ $whatsappPreview }}" target="_blank" rel="noopener noreferrer" class="link break-all font-mono" data-testid="whatsapp-preview">{{ $whatsappPreview }}</a>
                @else
                    <p class="text-muted">Enter a valid number and message to preview the link.</p>
                @endif
            </div>
        </section>
        <section class="panel space-y-4" aria-labelledby="chat-h">
            <h2 id="chat-h" class="text-2xl">Chatbot</h2>
            <p class="text-sm text-muted">A floating AI assistant in the bottom-right corner. It answers from your public catalogue, settings and the notes below, and guides visitors toward a quote request. It never sees trade-only or negotiated prices.</p>
            <div class="rounded-sm border px-3 py-2 text-sm {{ $chatProvider->isConfigured() ? 'border-success/40 badge-success' : 'border-warning/40 badge-warning' }}" data-testid="chat-provider-status">
                @if ($chatProvider->isConfigured())
                    AI provider configured: <strong>{{ $chatProvider->name() }}</strong> · model <code>{{ $chatProvider->model() }}</code>.
                    Credentials are present; run <code>php artisan assistant:check</code> on the server to confirm they work.
                @else
                    AI provider not configured (<code>{{ config('store.assistant.provider') }}</code>). For Cloudflare Workers AI set <code>CLOUDFLARE_ACCOUNT_ID</code>, <code>CLOUDFLARE_API_TOKEN</code> and <code>CLOUDFLARE_DEFAULT_MODEL</code> in the server's .env (see docs/CHATBOT.md). Until then, visitors who open the chat see an “offline” panel with your quote, email and WhatsApp links.
                @endif
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model.live="s.chatbot_enabled"> Show the chatbot</label>
            <x-field name="s.chatbot_name" model="s.chatbot_name" label="Assistant name" :placeholder="$s['brand_name'].' assistant'"/>
            <x-textarea-field name="s.chatbot_intro" model="s.chatbot_intro" label="Introductory message" rows="2" required help="Shown in the teaser bubble and at the top of the chat."/>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model="s.chatbot_teaser"> Pop up the introductory message</label>
                <x-field name="s.chatbot_teaser_delay" model="s.chatbot_teaser_delay" type="number" min="0" max="120" label="Pop-up delay (seconds)"/>
            </div>
            <x-textarea-field name="s.chatbot_knowledge" model="s.chatbot_knowledge" label="Additional knowledge for the assistant" rows="5" help="Optional facts and FAQs (e.g. shipping regions, sampling fees, certifications). The assistant only states facts it is given; keep this accurate."/>
        </section>
        <section class="panel space-y-4 xl:col-span-2">
            <h2 class="text-2xl">Homepage</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <x-field name="s.hero_autoplay" model="s.hero_autoplay" type="number" min="0" max="60" label="Banner slider: seconds per slide" help="0 turns autoplay off. Edit the slides in Homepage slides."/>
                <x-field name="s.products_autoplay" model="s.products_autoplay" type="number" min="0" max="60" label="Featured products carousel: seconds per move" help="0 turns autoplay off. Shows products marked “Featured”, then bestsellers."/>
            </div>
            <h3 class="pt-2 font-serif text-xl">Testimonials</h3>
            <p class="text-sm text-muted">Only add genuine testimonials you have permission to publish. One per line: <code>Quote | Name | Company</code>. Leave empty to hide the section.</p>
            <x-textarea-field name="testimonials" model="testimonials" rows="4"/>
        </section>
        <section class="panel grid gap-4 xl:col-span-2 xl:grid-cols-2">
            <h2 class="text-2xl xl:col-span-2">Policies</h2>
            <p class="text-sm text-muted xl:col-span-2">Shown on the footer policy pages. Leave a field empty to use the standard text (resources/policies), or paste your own. Supports simple formatting: <code>## Heading</code>, <code>- bullet</code>, <code>**bold**</code>, and placeholders such as <code>{brand}</code>, <code>{email}</code>, <code>{phone}</code>, <code>{address}</code>. Have the final wording reviewed by a legal adviser.</p>
            <x-textarea-field name="s.policy_terms" model="s.policy_terms" label="Terms of trade" rows="6"/>
            <x-textarea-field name="s.policy_privacy" model="s.policy_privacy" label="Privacy policy" rows="6"/>
            <x-textarea-field name="s.policy_shipping" model="s.policy_shipping" label="Shipping & delivery" rows="6"/>
            <x-textarea-field name="s.policy_returns" model="s.policy_returns" label="Returns & claims" rows="6"/>
        </section>
        <div class="xl:col-span-2"><button class="btn btn-primary btn-lg">Save settings</button></div>
    </form>
</div>
