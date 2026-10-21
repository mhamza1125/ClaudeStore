<div class="grid gap-3 sm:grid-cols-2">
    <x-select-field name="type" :id="$prefix.'_type'" label="Type" :options="['shipping' => 'Shipping', 'billing' => 'Billing']" :value="$address?->type ?? 'shipping'" required/>
    <x-field name="label" :id="$prefix.'_label'" label="Label" :value="$address?->label" placeholder="e.g. Warehouse"/>
    <x-field name="recipient" :id="$prefix.'_recipient'" label="Recipient" :value="$address?->recipient"/>
    <x-field name="phone" :id="$prefix.'_phone'" label="Phone" :value="$address?->phone"/>
    <x-field name="line1" :id="$prefix.'_line1'" label="Address line 1" :value="$address?->line1" required class="sm:col-span-2"/>
    <x-field name="line2" :id="$prefix.'_line2'" label="Address line 2" :value="$address?->line2" class="sm:col-span-2"/>
    <x-field name="city" :id="$prefix.'_city'" label="City" :value="$address?->city" required/>
    <x-field name="region" :id="$prefix.'_region'" label="State / region" :value="$address?->region"/>
    <x-field name="postal_code" :id="$prefix.'_postal'" label="Postal code" :value="$address?->postal_code"/>
    <x-select-field name="country" :id="$prefix.'_country'" label="Country" :options="countries()" :value="$address?->country" placeholder="Select…" required/>
    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_default" value="1" class="checkbox" @checked($address?->is_default)> Default for this type</label>
</div>
