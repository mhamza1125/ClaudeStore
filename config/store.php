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

    // Selectable WhatsApp greeting messages (Admin → Settings → WhatsApp).
    // Administrators may also enter a custom message instead.
    'whatsapp_presets' => [
        'services' => 'Hello! I want to know more about your services.',
        'wholesale' => 'Hello! I would like to know more about wholesale pricing and minimum order quantities.',
        'quote' => 'Hello! I would like to request a quote for a bulk order.',
        'private_label' => 'Hello! I am interested in custom or private-label leather products.',
        'samples' => 'Hello! Is it possible to order product samples before placing a wholesale order?',
    ],

    'images' => [
        // Name of the image provider binding: "null" (manual workflow only) or "removebg".
        'provider' => env('IMAGE_PROVIDER', 'null'),
        'removebg_key' => env('REMOVEBG_API_KEY'),
        'max_dimension' => 2000,
        'quality' => 82,
    ],
];
