<?php

namespace App\Support;

/**
 * Display swatches for the catalogue colour filter. Product colours are free text, so only
 * well-known leather colour words are matched; anything else gets no swatch (the label alone
 * is shown). Purely presentational — filtering still uses the exact stored colour value.
 */
final class LeatherColors
{
    /** @var array<string, string> lowercase keyword => CSS colour */
    private const MAP = [
        'black' => '#1c1917', 'white' => '#f5f5f4', 'ivory' => '#f2ecdd', 'cream' => '#ece2cc', 'beige' => '#d8c8aa',
        'tan' => '#b07d4f', 'camel' => '#b98a52', 'cognac' => '#9a5b2b', 'brown' => '#6b4429', 'chocolate' => '#4a2e1e',
        'coffee' => '#4e3524', 'espresso' => '#33231a', 'burgundy' => '#5e1f2b', 'oxblood' => '#4a1a1c', 'maroon' => '#5a1f24',
        'red' => '#9b2c22', 'navy' => '#1f2a44', 'blue' => '#2f4a7a', 'green' => '#3f5a3a', 'olive' => '#5f6038',
        'grey' => '#8a8782', 'gray' => '#8a8782', 'khaki' => '#a89a6a', 'natural' => '#c9a878', 'sand' => '#cdb892',
        'honey' => '#b9823f', 'mustard' => '#b58a2a', 'orange' => '#b8581f', 'rust' => '#9a4a28', 'pink' => '#d4a4a4',
    ];

    public static function css(string $name): ?string
    {
        $lower = mb_strtolower($name);
        foreach (self::MAP as $word => $css) {
            if (str_contains($lower, $word)) {
                return $css;
            }
        }

        return null;
    }
}
