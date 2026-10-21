<?php

namespace App\Livewire\Admin;

use App\Services\AuditLogger;
use App\Services\Settings;
use App\Support\Money;
use App\Support\WhatsApp;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsForm extends Component
{
    use WithFileUploads;

    public array $s = [];

    public string $testimonials = '';

    public $logo;

    public function mount(Settings $settings): void
    {
        $all = $settings->all();
        $this->s = [
            'brand_name' => $all['brand.name'], 'brand_tagline' => $all['brand.tagline'], 'brand_email' => $all['brand.email'],
            'brand_phone' => $all['brand.phone'], 'brand_address' => $all['brand.address'],
            'social_instagram' => (string) $all['social.instagram'], 'social_linkedin' => (string) $all['social.linkedin'], 'social_facebook' => (string) $all['social.facebook'],
            'sales_email' => (string) $all['sales.notification_email'],
            'tax_rate' => Money::bpsToPercentString((int) $all['tax.rate_bps']), 'tax_label' => $all['tax.label'], 'tax_on_shipping' => (bool) $all['tax.on_shipping'],
            'shipping_flat' => Money::toDecimalString((int) $all['shipping.flat_cents']),
            'min_order' => Money::toDecimalString((int) $all['order.min_value_cents']),
            'validity_days' => (int) $all['quote.validity_days'],
            'payment_terms' => (string) $all['quote.payment_terms'], 'delivery_terms' => (string) $all['quote.delivery_terms'],
            'acceptance' => (string) $all['quote.acceptance_instructions'],
            'newsletter' => (bool) $all['newsletter.enabled'],
            'hero_autoplay' => (int) $all['homepage.hero_autoplay_seconds'],
            'whatsapp_enabled' => (bool) $all['whatsapp.enabled'],
            'whatsapp_phone' => (string) $all['whatsapp.phone'],
            'whatsapp_preset' => (string) $all['whatsapp.message_preset'],
            'whatsapp_custom' => (string) $all['whatsapp.custom_message'],
            'policy_terms' => (string) $all['policies.terms'], 'policy_privacy' => (string) $all['policies.privacy'],
            'policy_shipping' => (string) $all['policies.shipping'], 'policy_returns' => (string) $all['policies.returns'],
        ];
        $this->testimonials = collect($all['homepage.testimonials'] ?? [])->map(fn ($t) => implode(' | ', [$t['quote'] ?? '', $t['name'] ?? '', $t['company'] ?? '']))->implode("\n");
    }

    public function save(Settings $settings): void
    {
        $money = ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'];
        $this->validate([
            's.brand_name' => ['required', 'string', 'max:80'], 's.brand_tagline' => ['nullable', 'string', 'max:160'],
            's.brand_email' => ['required', 'email'], 's.brand_phone' => ['nullable', 'string', 'max:40'], 's.brand_address' => ['nullable', 'string', 'max:255'],
            's.social_instagram' => ['nullable', 'url:https'], 's.social_linkedin' => ['nullable', 'url:https'], 's.social_facebook' => ['nullable', 'url:https'],
            's.sales_email' => ['nullable', 'email'],
            's.tax_rate' => ['required', 'regex:/^\d{1,2}(\.\d{1,2})?$/'], 's.tax_label' => ['required', 'string', 'max:40'],
            's.shipping_flat' => $money, 's.min_order' => $money,
            's.validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            's.payment_terms' => ['nullable', 'string', 'max:2000'], 's.delivery_terms' => ['nullable', 'string', 'max:2000'], 's.acceptance' => ['nullable', 'string', 'max:2000'],
            's.policy_terms' => ['nullable', 'string', 'max:50000'], 's.policy_privacy' => ['nullable', 'string', 'max:50000'],
            's.policy_shipping' => ['nullable', 'string', 'max:50000'], 's.policy_returns' => ['nullable', 'string', 'max:50000'],
            'testimonials' => ['nullable', 'string', 'max:10000'],
            's.hero_autoplay' => ['required', 'integer', 'min:0', 'max:60'],
            's.whatsapp_enabled' => ['boolean'],
            's.whatsapp_phone' => [$this->s['whatsapp_enabled'] ? 'required' : 'nullable', 'string', 'max:30',
                fn ($attr, $value, $fail) => filled($value) && ! WhatsApp::isValidPhone($value)
                    ? $fail('Enter the full international number with country code, e.g. +92 311 0012233.') : null],
            's.whatsapp_preset' => ['required', Rule::in([...array_keys(WhatsApp::presets()), 'custom'])],
            's.whatsapp_custom' => [$this->s['whatsapp_preset'] === 'custom' ? 'required' : 'nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], ['s.tax_rate.regex' => 'Enter a percentage such as 8.25.', 's.shipping_flat.regex' => 'Enter an amount such as 25.00.', 's.min_order.regex' => 'Enter an amount such as 500.00.']);

        $s = $this->s;
        $values = [
            'brand.name' => $s['brand_name'], 'brand.tagline' => $s['brand_tagline'], 'brand.email' => $s['brand_email'],
            'brand.phone' => $s['brand_phone'], 'brand.address' => $s['brand_address'],
            'social.instagram' => $s['social_instagram'] ?: null, 'social.linkedin' => $s['social_linkedin'] ?: null, 'social.facebook' => $s['social_facebook'] ?: null,
            'sales.notification_email' => $s['sales_email'] ?: null,
            'tax.rate_bps' => Money::parsePercentToBps($s['tax_rate']), 'tax.label' => $s['tax_label'], 'tax.on_shipping' => (bool) $s['tax_on_shipping'],
            'shipping.flat_cents' => Money::parse($s['shipping_flat']) ?? 0, 'order.min_value_cents' => Money::parse($s['min_order']) ?? 0,
            'quote.validity_days' => (int) $s['validity_days'],
            'quote.payment_terms' => $s['payment_terms'], 'quote.delivery_terms' => $s['delivery_terms'], 'quote.acceptance_instructions' => $s['acceptance'],
            'newsletter.enabled' => (bool) $s['newsletter'],
            'homepage.hero_autoplay_seconds' => (int) $s['hero_autoplay'],
            'whatsapp.enabled' => (bool) $s['whatsapp_enabled'],
            'whatsapp.phone' => WhatsApp::normalizePhone($s['whatsapp_phone']) ?: null,
            'whatsapp.message_preset' => $s['whatsapp_preset'],
            'whatsapp.custom_message' => trim((string) $s['whatsapp_custom']) ?: null,
            'policies.terms' => $s['policy_terms'] ?: null, 'policies.privacy' => $s['policy_privacy'] ?: null,
            'policies.shipping' => $s['policy_shipping'] ?: null, 'policies.returns' => $s['policy_returns'] ?: null,
            'homepage.testimonials' => collect(preg_split('/\R/', $this->testimonials))->map('trim')->filter()->map(function ($line) {
                [$quote, $name, $company] = array_pad(array_map('trim', explode('|', $line)), 3, '');

                return compact('quote', 'name', 'company');
            })->values()->all(),
        ];
        if ($this->logo) {
            $values['brand.logo_path'] = $this->logo->store('brand', 'public');
        }

        $before = $settings->all();
        $changed = collect($values)->filter(fn ($v, $k) => ($before[$k] ?? null) !== $v)->map(fn ($v, $k) => ['from' => $before[$k] ?? null, 'to' => $v])
            ->except(['policies.terms', 'policies.privacy', 'policies.shipping', 'policies.returns'])->all();
        $settings->set($values);
        AuditLogger::log('settings.updated', null, 'Store settings updated.', $changed);
        $this->reset('logo');
        session()->flash('status', 'Settings saved.');
    }

    public function removeLogo(Settings $settings): void
    {
        if ($path = $settings->get('brand.logo_path')) {
            Storage::disk('public')->delete($path);
        }
        $settings->set(['brand.logo_path' => null]);
    }

    public function render(Settings $settings)
    {
        $message = $this->s['whatsapp_preset'] === 'custom' ? trim((string) $this->s['whatsapp_custom']) : (WhatsApp::presets()[$this->s['whatsapp_preset']] ?? '');

        return view('livewire.admin.settings-form', [
            'logoPath' => $settings->get('brand.logo_path'),
            'whatsappPresets' => WhatsApp::presets() + ['custom' => 'Custom message…'],
            // Live preview of the link the button will open (from the unsaved form values).
            'whatsappPreview' => WhatsApp::isValidPhone($this->s['whatsapp_phone']) && $message !== '' ? WhatsApp::link($this->s['whatsapp_phone'], $message) : null,
        ])
            ->layout('components.layouts.admin', ['title' => 'Settings']);
    }
}
