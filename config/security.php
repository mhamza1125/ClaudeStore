<?php

/*
| HTTP hardening (audit F-08, F-18). Defaults keep today's behaviour. See docs/ENV.md and
| docs/HOSTINGER.md before changing either value.
*/

return [
    // Strict-Transport-Security max-age in seconds. 0 = off (default). Enable only after HTTPS
    // works on every page; start short (e.g. 300) and raise it once nothing breaks.
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 0),

    // Proxies whose X-Forwarded-* headers are trusted: comma-separated IPs/CIDRs, or "*".
    // Empty (default) = trust none, i.e. today's behaviour.
    'trusted_proxies' => env('TRUSTED_PROXIES'),
];
