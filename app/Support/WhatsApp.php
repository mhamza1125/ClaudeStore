<?php

namespace App\Support;

/**
 * Builds the floating WhatsApp "click to chat" link from Admin → Settings.
 */
final class WhatsApp
{
    public const BASE_URL = 'https://api.whatsapp.com/send/';

    /** @return array<string,string> preset key => message */
    public static function presets(): array
    {
        return config('store.whatsapp_presets', []);
    }

    /** "+92 311-0012233" → "923110012233" (WhatsApp expects digits only, with country code). */
    public static function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    public static function isValidPhone(?string $phone): bool
    {
        return (bool) preg_match('/^[1-9]\d{7,14}$/', self::normalizePhone($phone));
    }

    /** The message chosen in settings: a preset, or the custom text when "custom" is selected. */
    public static function configuredMessage(): string
    {
        $preset = (string) setting('whatsapp.message_preset', 'services');
        if ($preset === 'custom' && filled(setting('whatsapp.custom_message'))) {
            return trim((string) setting('whatsapp.custom_message'));
        }

        return self::presets()[$preset] ?? self::presets()['services'] ?? '';
    }

    /**
     * Form-encoded query (spaces as "+", "!" as "%21"), matching WhatsApp's own share links:
     * https://api.whatsapp.com/send/?phone=923110012233&text=Hello%21+I+want...&type=phone_number&app_absent=0
     */
    public static function link(string $phone, string $message): string
    {
        return self::BASE_URL.'?'.http_build_query([
            'phone' => self::normalizePhone($phone),
            'text' => $message,
            'type' => 'phone_number',
            'app_absent' => 0,
        ], '', '&', PHP_QUERY_RFC1738);
    }

    /** Link for the storefront button, or null when disabled or not configured. */
    public static function configuredLink(): ?string
    {
        if (! setting('whatsapp.enabled') || ! self::isValidPhone(setting('whatsapp.phone'))) {
            return null;
        }

        return self::link((string) setting('whatsapp.phone'), self::configuredMessage());
    }
}
