<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Integer minor-unit (cents) money helpers. No floating point is used anywhere:
 * parsing works on the decimal string and percentages are basis points.
 */
final class Money
{
    public static function format(?int $cents, string $currency = 'USD'): string
    {
        if ($cents === null) {
            return '—';
        }

        $symbol = match (strtoupper($currency)) {
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', default => strtoupper($currency).' ',
        };
        $negative = $cents < 0;
        $cents = abs($cents);
        $major = intdiv($cents, 100);
        $minor = str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').$symbol.number_format($major).'.'.$minor;
    }

    /** "12.5" / "1,234.56" / "$18" -> cents. Throws on anything ambiguous. */
    public static function parse(string|int|null $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value)) {
            return $value * 100;
        }
        $value = trim(str_replace([',', '$', ' '], '', $value));
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $m)) {
            throw new InvalidArgumentException("Invalid money amount [{$value}].");
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    /** Cents -> "12.50" for form inputs. */
    public static function toDecimalString(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Apply basis points to an amount, rounding half up. 1000 bps = 10%. */
    public static function percentOf(int $cents, int $bps): int
    {
        return intdiv($cents * $bps + 5000, 10000);
    }

    /** "7.5" (percent) -> 750 bps. */
    public static function parsePercentToBps(string|int|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $value = trim((string) $value);
        if (! preg_match('/^(\d{1,3})(?:\.(\d{1,2}))?$/', $value, $m)) {
            throw new InvalidArgumentException("Invalid percentage [{$value}].");
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    public static function bpsToPercentString(int $bps): string
    {
        $s = intdiv($bps, 100).'.'.str_pad((string) ($bps % 100), 2, '0', STR_PAD_LEFT);

        return rtrim(rtrim($s, '0'), '.');
    }
}
