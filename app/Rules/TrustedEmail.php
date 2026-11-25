<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Accepts only real, permanent mailbox addresses:
 *  - not a disposable / temporary provider (≈10k domains in resources/data, refreshable with
 *    `php artisan emails:update-blocklist`, plus store.email.blocked_domains), including subdomains;
 *  - the domain must exist and be able to receive mail: an MX record, or (RFC 5321 implicit MX)
 *    an A/AAAA record when it has no MX at all. Domains declaring "null MX" are rejected.
 *
 * Use alongside the 'email' rule, which validates the format.
 */
class TrustedEmail implements ValidationRule
{
    public const MESSAGE = 'Please use a permanent email address. Temporary, disposable or unreachable addresses are not accepted.';

    /** Well-known providers: always accepted, no DNS lookup needed. */
    private const KNOWN_PROVIDERS = [
        'gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'live.com', 'msn.com', 'yahoo.com', 'ymail.com',
        'icloud.com', 'me.com', 'mac.com', 'aol.com', 'proton.me', 'protonmail.com', 'zoho.com', 'gmx.com', 'gmx.de',
        'mail.com', 'yandex.com', 'hotmail.co.uk', 'yahoo.co.uk', 'outlook.co.uk', 'live.co.uk',
    ];

    /** Shared blocklist file paths: the bundled list, and the copy refreshed by the update command. */
    public const BUNDLED_LIST = 'data/disposable-email-domains.txt';

    public const UPDATED_LIST = 'app/disposable-email-domains.txt';

    private static ?array $blocklist = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '@')) {
            return; // the 'email' rule reports format problems
        }

        $domain = strtolower(rtrim(trim(substr(strrchr($value, '@'), 1)), '.'));
        if ($domain === '' || ! self::isAcceptableDomain($domain)) {
            $fail(self::MESSAGE);
        }
    }

    public static function isAcceptableDomain(string $domain): bool
    {
        $domain = strtolower($domain);
        $allowed = array_map('strtolower', config('store.email.allowed_domains', []));
        if (in_array($domain, $allowed, true) || in_array($domain, self::KNOWN_PROVIDERS, true)) {
            return true;
        }
        // IP literals and single-label hosts are never real mailbox domains for customers.
        if (str_starts_with($domain, '[') || ! str_contains($domain, '.')) {
            return false;
        }
        if (self::isBlocked($domain)) {
            return false;
        }

        return ! config('store.email.verify_mx', true) || self::hasMailServer($domain);
    }

    /** The domain or any parent domain (e.g. x.mailinator.com) is on the blocklist. */
    public static function isBlocked(string $domain): bool
    {
        $list = self::blocklist();
        $parts = explode('.', $domain);
        while (count($parts) >= 2) {
            if (isset($list[implode('.', $parts)])) {
                return true;
            }
            array_shift($parts);
        }

        return false;
    }

    /** @return array<string, true> */
    public static function blocklist(): array
    {
        if (self::$blocklist !== null) {
            return self::$blocklist;
        }

        $domains = config('store.email.blocked_domains', []);
        foreach ([storage_path(self::UPDATED_LIST), resource_path(self::BUNDLED_LIST)] as $file) {
            if (is_readable($file)) {
                $domains = [...$domains, ...file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)];
                break; // the refreshed copy replaces the bundled one
            }
        }

        $list = [];
        foreach ($domains as $d) {
            $d = strtolower(trim($d));
            if ($d !== '' && ! str_starts_with($d, '#')) {
                $list[$d] = true;
            }
        }

        return self::$blocklist = $list;
    }

    public static function flushBlocklist(): void
    {
        self::$blocklist = null;
    }

    /** @var (Closure(string, int): (array|false))|null test seam; null = dns_get_record */
    private static ?Closure $dnsResolver = null;

    /** Replace the DNS resolver (tests). The closure returns records, or false when the lookup failed. */
    public static function resolveDnsUsing(?Closure $resolver): void
    {
        self::$dnsResolver = $resolver;
    }

    /**
     * Rejects only on a definitive "no mail records" answer. If DNS is unavailable on the
     * server or the lookup fails, the address is accepted and a warning logged, so a hosting
     * DNS problem never blocks quote requests or registrations (audit F-33).
     */
    private static function hasMailServer(string $domain): bool
    {
        $result = self::lookupMx($domain);
        if ($result === null) {
            Log::warning('Email domain check skipped: DNS lookup failed', ['domain' => $domain]);

            return true;
        }

        return $result;
    }

    /** true = can receive mail; false = definitively cannot; null = lookup failed (never cached). */
    private static function lookupMx(string $domain): ?bool
    {
        $key = 'email-mx:'.$domain;
        if (Cache::has($key)) {
            return (bool) Cache::get($key);
        }

        $mx = self::dns($domain.'.', DNS_MX);
        if ($mx === false) {
            return null;
        }
        if ($mx !== []) {
            // RFC 7505 "null MX" (target ".") means the domain accepts no mail.
            $answer = collect($mx)->contains(fn ($r) => trim((string) ($r['target'] ?? ''), '. ') !== '');
        } else {
            // No MX at all: mail is delivered to the domain's own address (implicit MX).
            $a = self::dns($domain.'.', DNS_A);
            $aaaa = $a === [] ? self::dns($domain.'.', DNS_AAAA) : [];
            if ($a === false || $aaaa === false) {
                return null;
            }
            $answer = $a !== [] || $aaaa !== [];
        }

        Cache::put($key, $answer, now()->addDay()); // only definitive answers are cached

        return $answer;
    }

    /** @return array<int, array<string, mixed>>|false */
    private static function dns(string $host, int $type): array|false
    {
        try {
            if (self::$dnsResolver) {
                return (self::$dnsResolver)($host, $type);
            }
            if (! function_exists('dns_get_record')) {
                return false;
            }

            return @dns_get_record($host, $type);
        } catch (\Throwable) {
            return false;
        }
    }
}
