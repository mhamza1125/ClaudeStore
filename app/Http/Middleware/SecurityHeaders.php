<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline HTTP security headers (audit F-08). Headers a controller already set are kept
 * (e.g. the image route's SVG Content-Security-Policy).
 *
 * No Content-Security-Policy yet: Livewire and Alpine use inline scripts; a report-only CSP is
 * the proposed next step (docs/HOSTINGER.md). HSTS is off unless security.hsts_max_age > 0.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $defaults = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()',
        ];
        foreach ($defaults as $name => $value) {
            if (! $headers->has($name)) {
                $headers->set($name, $value);
            }
        }

        // Secure quotation links carry a secret token in the URL: never leak it in a Referer
        // header, and never let a shared cache keep the page.
        if ($request->is('q/*')) {
            $headers->set('Referrer-Policy', 'no-referrer');
            $headers->set('Cache-Control', 'no-store, private');
        }

        $hsts = (int) config('security.hsts_max_age', 0);
        if ($hsts > 0 && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age='.$hsts);
        }

        return $response;
    }
}
