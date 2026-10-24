<?php

namespace App\Support;

/**
 * Site themes: three palettes plus "system" (follows the OS: dark, or the configured
 * light palette). Admin → Settings → Appearance sets the default, the light palette used
 * by "system", and whether visitors may switch. Resolution happens client-side before
 * CSS paints (partials/head.blade.php) so there is no flash of the wrong theme.
 */
final class Theme
{
    public const PALETTES = ['white' => 'White', 'mid' => 'Mid', 'dark' => 'Dark'];

    /** choice => [label, icon] for the visitor toggle */
    public const CHOICES = [
        'white' => ['White', 'sun'],
        'mid' => ['Mid', 'swatch'],
        'dark' => ['Dark', 'moon'],
        'system' => ['System', 'monitor'],
    ];

    /** Earlier versions stored "light" (today's Mid palette). */
    public static function normalize(?string $choice): ?string
    {
        $choice = $choice === 'light' ? 'mid' : $choice;

        return array_key_exists((string) $choice, self::CHOICES) ? $choice : null;
    }

    public static function allowSwitch(): bool
    {
        return (bool) setting('theme.allow_switch', true);
    }

    /** data-* attributes for <html>; values are escaped. */
    public static function htmlAttributes(bool $forceSwitch = false): string
    {
        $user = auth()->user();
        $attrs = [
            'data-theme-default' => self::normalize(setting('theme.default')) ?? 'mid',
            'data-theme-light' => in_array(setting('theme.system_light'), ['white', 'mid'], true) ? setting('theme.system_light') : 'mid',
            'data-theme-switch' => ($forceSwitch || self::allowSwitch()) ? '1' : '0',
            'data-user-theme' => $user ? (string) self::normalize($user->theme) : null,
            'data-theme-url' => $user ? route('preferences.theme') : null,
        ];

        return collect($attrs)->filter(fn ($v) => $v !== null)
            ->map(fn ($v, $k) => $k.'="'.e($v).'"')->implode(' ');
    }
}
