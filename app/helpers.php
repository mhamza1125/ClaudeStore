<?php

use App\Services\Settings;
use App\Support\Money;

if (! function_exists('money')) {
    function money(?int $cents, ?string $currency = null): string
    {
        return Money::format($cents, $currency ?? config('store.currency', 'USD'));
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('countries')) {
    /** ISO 3166-1 alpha-2 => name. */
    function countries(): array
    {
        return require resource_path('data/countries.php');
    }
}
