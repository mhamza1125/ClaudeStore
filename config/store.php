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
    'sales_notification_email' => env('SALES_NOTIFICATION_EMAIL', 'info@leamate.com'),
    'quote_rate_limit_per_hour' => (int) env('QUOTE_RATE_LIMIT_PER_HOUR', 5),

    // Shorter labels for categories in the header menus only (slug => label). Category pages,
    // breadcrumbs, titles and the footer keep the full category name.
    'nav_labels' => [
        'leather-accessories' => 'Accessories',
    ],

    // Selectable WhatsApp greeting messages (Admin → Settings → WhatsApp).
    // Administrators may also enter a custom message instead.
    'whatsapp_presets' => [
        'services' => 'Hello! I want to know more about your services.',
        'wholesale' => 'Hello! I would like to know more about wholesale pricing and minimum order quantities.',
        'quote' => 'Hello! I would like to request a quote for a bulk order.',
        'private_label' => 'Hello! I am interested in custom or private-label leather products.',
        'samples' => 'Hello! Is it possible to order product samples before placing a wholesale order?',
    ],

    // Website chatbot (Admin → Settings → Chatbot). The API key is read from the
    // environment only and is never sent to the browser.
    'assistant' => [
        // "cloudflare" (default), "anthropic", or "null" (offline). ASSISTANT_PROVIDER is the older name.
        'provider' => env('AI_DEFAULT_PROVIDER', env('ASSISTANT_PROVIDER', 'cloudflare')),
        'max_tokens' => (int) env('ASSISTANT_MAX_TOKENS', 1024),
        'timeout' => (float) env('ASSISTANT_TIMEOUT', 30),
        'history_messages' => 20, // kept in the visitor's session
        'max_message_length' => 1000,
        'max_products_in_context' => 150,
        'rate_per_minute' => (int) env('ASSISTANT_RATE_PER_MINUTE', 8),
        'rate_per_day' => (int) env('ASSISTANT_RATE_PER_DAY', 80),

        // Provider-specific options. Credentials live in config/services.php (from .env).
        'cloudflare' => [
            // Workers AI model ID. Llama 3.3 70B is published as "@cf/meta/llama-3.3-70b-instruct-fp8-fast".
            'model' => env('CLOUDFLARE_DEFAULT_MODEL', '@cf/meta/llama-3.3-70b-instruct-fp8-fast'),
            // Override to route through Cloudflare AI Gateway, e.g. https://gateway.ai.cloudflare.com/v1/{account}/{gateway}/workers-ai
            'base_url' => env('CLOUDFLARE_AI_BASE_URL', 'https://api.cloudflare.com/client/v4'),
            'temperature' => (float) env('CLOUDFLARE_AI_TEMPERATURE', 0.3),
        ],
        'anthropic' => [
            'model' => env('ASSISTANT_MODEL', 'claude-opus-5-5'),
            'effort' => env('ASSISTANT_EFFORT', 'low'),
        ],
    ],

    'images' => [
        // Name of the image provider binding: "null" (manual workflow only) or "removebg".
        'provider' => env('IMAGE_PROVIDER', 'null'),
        'removebg_key' => env('REMOVEBG_API_KEY'),
        'max_dimension' => 2000,
        'quality' => 82,
    ],
];
