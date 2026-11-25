<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Policy page text (terms, privacy, shipping, returns). Admin → Settings → Policies overrides
 * each page; when a field is left empty the standard text in resources/policies/{key}.md is
 * used. Both may use placeholders such as {brand} or {email}, filled from current settings so
 * the pages follow brand and contact changes.
 */
final class Policies
{
    public static function text(string $key): string
    {
        $text = trim((string) setting('policies.'.$key));
        if ($text === '' && is_file($file = resource_path("policies/{$key}.md"))) {
            $text = trim((string) file_get_contents($file));
        }

        return strtr($text, self::placeholders());
    }

    /** Markdown (headings, lists, links) to HTML. Raw HTML is stripped; line breaks are kept. */
    public static function html(string $key): ?HtmlString
    {
        $text = self::text($key);

        return $text === '' ? null : new HtmlString(Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'renderer' => ['soft_break' => "<br>\n"],
        ]));
    }

    /** @return array<string, string> */
    public static function placeholders(): array
    {
        return [
            '{brand}' => (string) setting('brand.name'),
            '{email}' => (string) setting('brand.email'),
            '{phone}' => (string) setting('brand.phone'),
            '{address}' => (string) setting('brand.address'),
            '{website}' => rtrim((string) config('app.url'), '/'),
            '{currency}' => (string) config('store.currency'),
            '{quote_validity_days}' => (string) setting('quote.validity_days'),
            '{payment_terms}' => (string) setting('quote.payment_terms'),
            '{delivery_terms}' => (string) setting('quote.delivery_terms'),
        ];
    }
}
