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
        <section class="panel space-y-4 xl:col-span-2">
            <h2 class="text-2xl">Homepage</h2>
            <x-field name="s.hero_autoplay" model="s.hero_autoplay" type="number" min="0" max="60" label="Hero carousel: seconds per slide" help="0 turns autoplay off (visitors use the arrows and dots). Slides are the products marked “Featured” (then bestsellers), up to five." class="max-w-sm"/>
            <h3 class="pt-2 font-serif text-xl">Testimonials</h3>
            <p class="text-sm text-muted">Only add genuine testimonials you have permission to publish. One per line: <code>Quote | Name | Company</code>. Leave empty to hide the section.</p>
            <x-textarea-field name="testimonials" model="testimonials" rows="4"/>
        </section>
        <section class="panel grid gap-4 xl:col-span-2 xl:grid-cols-2">
            <h2 class="text-2xl xl:col-span-2">Policies</h2>
            <p class="text-sm text-muted xl:col-span-2">Plain text, shown on the footer policy pages. Must be supplied by the business (ideally legally reviewed).</p>
            <x-textarea-field name="s.policy_terms" model="s.policy_terms" label="Terms of trade" rows="6"/>
            <x-textarea-field name="s.policy_privacy" model="s.policy_privacy" label="Privacy policy" rows="6"/>
            <x-textarea-field name="s.policy_shipping" model="s.policy_shipping" label="Shipping & delivery" rows="6"/>
            <x-textarea-field name="s.policy_returns" model="s.policy_returns" label="Returns & claims" rows="6"/>
        </section>
        <div class="xl:col-span-2"><button class="btn btn-primary btn-lg">Save settings</button></div>
    </form>
</div>
