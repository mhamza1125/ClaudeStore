<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Runtime-editable settings with config fallbacks. Values are JSON-encoded in
 * the `settings` table and cached as a single array.
 */
class Settings
{
    private const CACHE_KEY = 'app.settings.v1';

    private ?array $loaded = null;

    public static function defaults(): array
    {
        return [
            'brand.name' => config('brand.name'),
            'brand.tagline' => config('brand.tagline'),
            'brand.logo_path' => config('brand.logo_path'),
            'brand.email' => config('brand.email'),
            'brand.phone' => config('brand.phone'),
            'brand.address' => config('brand.address'),
            'social.instagram' => config('brand.social.instagram'),
            'social.linkedin' => config('brand.social.linkedin'),
            'social.facebook' => config('brand.social.facebook'),
            'sales.notification_email' => config('store.sales_notification_email'),
            'tax.rate_bps' => config('store.tax_rate_bps'),
            'tax.label' => config('store.tax_label'),
            'tax.on_shipping' => config('store.tax_on_shipping'),
            'shipping.flat_cents' => config('store.shipping_flat_cents'),
            'order.min_value_cents' => config('store.min_order_value_cents'),
            'quote.validity_days' => config('store.quote_validity_days'),
            'quote.payment_terms' => '50% deposit on order confirmation, balance before dispatch. Payment is arranged offline by bank transfer.',
            'quote.delivery_terms' => 'Ex-works unless otherwise agreed. Lead times are confirmed on order.',
            'quote.acceptance_instructions' => 'Review the quotation, then use the secure link to accept or reject it. Acceptance is subject to stock and production confirmation. No payment is taken online.',
            'newsletter.enabled' => false,
            // B2B price access (Admin → B2B settings, see docs/B2B.md). On by default: guests
            // must register to see prices. Off = prices follow the per-product "public pricing"
            // flag and wholesale approval only (anyone sees public list prices).
            'b2b.enabled' => true,
            'b2b.guest_prices' => true, // guests see public list prices (ignored when registration is required)
            'b2b.require_registration' => true,
            'b2b.require_email_verification' => false,
            'b2b.require_approval' => false,
            'b2b.guest_inquiries' => true, // guests may submit quote requests without an account
            'images.watermark_enabled' => true,
            'images.watermark_mode' => 'embedded', // embedded (drawn into served copies) | overlay (CSS on the page)
            'images.watermark_text' => null, // defaults to the business name
            'images.watermark_opacity' => 22, // percent
            'images.watermark_personalized' => false, // adds the signed-in customer's email or ID
            'images.watermark_identifier' => 'email', // email | id
            'theme.default' => 'mid', // white | mid | dark | system
            'theme.system_light' => 'mid', // palette used by "system" when the OS is in light mode
            'theme.allow_switch' => true, // show the theme switcher to visitors
            'homepage.testimonials' => [],
            'homepage.hero_autoplay_seconds' => 6, // banner slider; 0 = manual navigation only
            'homepage.products_autoplay_seconds' => 5, // featured products carousel; 0 = manual // [{quote, name, company}] — only genuine, supplied content
            'whatsapp.enabled' => true,
            'whatsapp.phone' => '923314657496', // international format, digits only
            'whatsapp.message_preset' => 'services', // a key of store.whatsapp_presets, or "custom"
            'whatsapp.custom_message' => null,
            'chatbot.enabled' => false, // needs ANTHROPIC_API_KEY to answer; offline contact panel otherwise
            'chatbot.assistant_name' => null, // defaults to "<brand> assistant"
            'chatbot.intro_message' => 'Hi! Ask me about our leather goods, minimum orders or custom work — or I can help you request a quote.',
            'chatbot.show_teaser' => true,
            'chatbot.teaser_delay_seconds' => 5,
            'chatbot.knowledge' => null, // extra facts/FAQ the assistant may use
            'policies.terms' => null,
            'policies.privacy' => null,
            'policies.shipping' => null,
            'policies.returns' => null,
        ];
    }

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $stored = [];
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')
                    ->map(fn ($v) => json_decode($v, true))->all();
            });
        } catch (Throwable) {
            // Database unavailable (e.g. during install): fall back to defaults.
        }

        return $this->loaded = array_merge(self::defaults(), $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return $value ?? $default;
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->forgetLoaded();
    }

    /** Drop the in-memory copy only (long-running workers call this between jobs). */
    public function forgetLoaded(): void
    {
        $this->loaded = null;
    }
}
