<?php

/*
| Brand identity defaults. Every value here can be overridden at runtime from
| Admin → Settings (stored in the `settings` table).
*/

return [
    'name' => env('BRAND_NAME', 'Leamate'),
    'tagline' => env('BRAND_TAGLINE', 'Leather Goods & Accessories'),
    // Default meta description. The homepage title is "<name> | <tagline>".
    'description' => env('BRAND_DESCRIPTION', 'Leamate makes premium leather bags, belts, wallets and accessories in Sialkot, Pakistan, for retailers, distributors and brands, with wholesale pricing and private-label manufacturing.'),
    'logo_path' => null, // relative to the public disk, uploaded from Admin → Settings
    // Shipped logo, used when none is uploaded (relative to public/).
    'default_logo' => 'images/brand/leamate-logo.png',
    'email' => env('BRAND_EMAIL', 'info@leamate.com'),
    'phone' => env('BRAND_PHONE', '(+92) 331-4657496'),
    'address' => env('BRAND_ADDRESS', 'Capital Road, Sialkot, Pakistan'),
    'social' => [
        'instagram' => env('BRAND_INSTAGRAM'),
        'linkedin' => env('BRAND_LINKEDIN'),
        'facebook' => env('BRAND_FACEBOOK'),
        'youtube' => env('BRAND_YOUTUBE'),
        'tiktok' => env('BRAND_TIKTOK'),
        'pinterest' => env('BRAND_PINTEREST'),
    ],
    // Reference prefixes: LM-Q-YYYY-000123 and LM-SO-YYYY-000123
    'reference_prefix' => env('BRAND_REFERENCE_PREFIX', 'LM'),
];
