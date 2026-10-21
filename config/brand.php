<?php

/*
| Brand identity defaults. Every value here can be overridden at runtime from
| Admin → Settings (stored in the `settings` table). "Atelier Hide" is a demo
| placeholder name, not a real business.
*/

return [
    'name' => env('BRAND_NAME', 'Atelier Hide'),
    'tagline' => env('BRAND_TAGLINE', 'Leather goods, made for trade'),
    'logo_path' => null, // relative to the public disk, uploaded from Admin → Settings
    'email' => env('BRAND_EMAIL', 'trade@atelierhide.test'),
    'phone' => env('BRAND_PHONE', '+1 (000) 000-0000'),
    'address' => env('BRAND_ADDRESS', 'Demo address — update in Admin → Settings'),
    'social' => [
        'instagram' => env('BRAND_INSTAGRAM'),
        'linkedin' => env('BRAND_LINKEDIN'),
        'facebook' => env('BRAND_FACEBOOK'),
    ],
    // Reference prefixes: AH-Q-YYYY-000123 and AH-SO-YYYY-000123
    'reference_prefix' => env('BRAND_REFERENCE_PREFIX', 'AH'),
];
