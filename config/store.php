<?php

/*
| Commercial defaults. Admin → Settings overrides these at runtime.
| All money is integer cents; all percentages are basis points (100 = 1%).
*/

return [
    'currency' => 'USD',
    // Informational only: shown on the quote form as "preferred currency".
    'display_currencies' => ['USD', 'EUR', 'GBP', 'AED', 'CAD', 'AUD'],
    'tax_rate_bps' => (int) env('STORE_TAX_RATE_BPS', 0),
    'tax_label' => 'Tax',
    'tax_on_shipping' => true,
    'shipping_flat_cents' => (int) env('STORE_SHIPPING_FLAT_CENTS', 0),
    'min_order_value_cents' => (int) env('STORE_MIN_ORDER_VALUE_CENTS', 0),
    'quote_validity_days' => 30,
    'sales_notification_email' => env('SALES_NOTIFICATION_EMAIL', 'sales@atelierhide.test'),
    'quote_rate_limit_per_hour' => (int) env('QUOTE_RATE_LIMIT_PER_HOUR', 5),

    'images' => [
        // Name of the image provider binding: "null" (manual workflow only) or "removebg".
        'provider' => env('IMAGE_PROVIDER', 'null'),
        'removebg_key' => env('REMOVEBG_API_KEY'),
        'max_dimension' => 2000,
        'quality' => 82,
    ],
];
