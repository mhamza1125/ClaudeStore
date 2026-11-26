<?php

namespace Tests\Feature\Security;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

/**
 * Audit F-08 / F-18: baseline security headers on every web response; stricter referrer and
 * caching on secure quotation links; HSTS and trusted proxies only when configured.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    public function test_baseline_headers_on_storefront_pages(): void
    {
        $r = $this->get('/');
        $r->assertOk();
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $r->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $r->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=()', (string) $r->headers->get('Permissions-Policy'));
        $r->assertHeaderMissing('Strict-Transport-Security');
        $r->assertHeaderMissing('Content-Security-Policy'); // no CSP yet (inline Livewire/Alpine scripts)
    }

    public function test_secure_quote_links_send_no_referrer_and_are_not_cached(): void
    {
        $quote = Quote::factory()->create(['status' => QuoteStatus::AwaitingResponse, 'version' => 1,
            'valid_until' => now()->addDays(5), 'access_token_hash' => hash('sha256', 'tok')]);
        $quote->versions()->create(['version' => 1, 'snapshot' => ['items' => [], 'totals' => ['currency' => 'USD']]]);

        $r = $this->get("/q/{$quote->reference}/tok");
        $r->assertOk();
        $r->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', (string) $r->headers->get('Cache-Control'));

        // A wrong token's 404 gets the same protection.
        $this->assertSame('no-referrer', $this->get("/q/{$quote->reference}/nope")->headers->get('Referrer-Policy'));
    }

    public function test_hsts_only_when_enabled_and_only_over_https(): void
    {
        config(['security.hsts_max_age' => 300]);
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=300');
    }

    public function test_existing_specific_headers_are_kept(): void
    {
        // The chatbot endpoint and JSON responses still work and carry the baseline headers.
        $this->postJson('/assistant/messages', ['message' => ''])->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->getJson('/search/suggest?q=ab')->assertOk()->assertHeader('Cache-Control', 'max-age=30, private');
    }

    public function test_no_proxies_are_trusted_by_default(): void
    {
        $this->assertNull(config('security.trusted_proxies'));
        $this->get('/', ['X-Forwarded-For' => '203.0.113.9'])->assertOk();
        $this->assertSame('127.0.0.1', request()->ip());
    }

    public function test_trusted_proxies_come_from_configuration(): void
    {
        config(['security.trusted_proxies' => '127.0.0.1']);
        $this->app->make(\App\Providers\AppServiceProvider::class, ['app' => $this->app])->boot();
        $this->get('/', ['X-Forwarded-For' => '203.0.113.9'])->assertOk();
        $this->assertSame('203.0.113.9', request()->ip());
    }
}
