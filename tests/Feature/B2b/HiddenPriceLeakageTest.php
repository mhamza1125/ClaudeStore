<?php

namespace Tests\Feature\B2b;

use App\Models\Product;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Characterization (audit §6, §9.4): a price the viewer may not see never appears in the
 * product page HTML, its Livewire snapshot, JSON-LD, meta/OG tags, or the sitemap.
 * Each product uses a distinctive amount so any leak is detectable as text.
 */
class HiddenPriceLeakageTest extends TestCase
{
    use RefreshDatabase;

    private const LEAK_MARKERS = [
        'trade' => ['987.65', '98765', '987,65'],
        'onrequest' => ['876.54', '87654'],
    ];

    private function products(): array
    {
        return [
            'trade' => Product::factory()->create(['public_pricing' => false, 'base_price_cents' => 98765]),
            'onrequest' => Product::factory()->create(['price_on_request' => true, 'base_price_cents' => 87654]),
            'public' => Product::factory()->create(['public_pricing' => true, 'base_price_cents' => 76543]),
        ];
    }

    private function assertNoLeak(string $content, string $kind, string $where): void
    {
        foreach (self::LEAK_MARKERS[$kind] as $marker) {
            $this->assertStringNotContainsString($marker, $content, "{$kind} price leaked in {$where}");
        }
    }

    public function test_guest_with_default_b2b_settings_sees_no_hidden_price_in_any_channel(): void
    {
        $p = $this->products();

        foreach (['trade', 'onrequest'] as $kind) {
            $html = $this->get(route('products.show', $p[$kind]))->assertOk()->getContent();
            $this->assertStringContainsString('wire:snapshot', $html, 'Livewire snapshot must be part of the checked HTML');
            $this->assertStringContainsString('application/ld+json', $html);
            $this->assertStringContainsString('og:title', $html);
            $this->assertNoLeak($html, $kind, 'product page (HTML, Livewire snapshot, JSON-LD, meta, OG)');
        }
        // Default settings hide even public list prices from guests.
        $this->assertStringNotContainsString('765.43', $this->get(route('products.show', $p['public']))->getContent());

        Cache::forget('seo.sitemap');
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertNoLeak($sitemap, 'trade', 'sitemap');
        $this->assertNoLeak($sitemap, 'onrequest', 'sitemap');

        $catalogue = $this->get('/products')->getContent();
        $this->assertNoLeak($catalogue, 'trade', 'catalogue');
        $this->assertNoLeak($catalogue, 'onrequest', 'catalogue');
    }

    public function test_with_b2b_off_guests_see_public_prices_but_never_trade_or_on_request(): void
    {
        app(Settings::class)->set(['b2b.enabled' => false]);
        $p = $this->products();

        $public = $this->get(route('products.show', $p['public']))->assertOk()->getContent();
        $this->assertStringContainsString('765.43', $public, 'positive control: the visible price is rendered');
        $this->assertStringContainsString('"price":"765.43"', $public, 'positive control: JSON-LD offer for a visible price');

        foreach (['trade', 'onrequest'] as $kind) {
            $html = $this->get(route('products.show', $p[$kind]))->assertOk()->getContent();
            $this->assertNoLeak($html, $kind, 'product page with B2B off');
            $this->assertStringNotContainsString('"offers"', $html, "{$kind}: no Offer in JSON-LD");
        }
    }

    public function test_on_request_price_is_hidden_even_from_a_signed_in_customer_with_access(): void
    {
        $p = $this->products();
        $this->actingAs($this->customer());

        $html = $this->get(route('products.show', $p['onrequest']))->assertOk()->getContent();
        $this->assertNoLeak($html, 'onrequest', 'product page for a customer with access');
        $this->assertNoLeak($this->getJson('/search/suggest?q='.urlencode(substr($p['onrequest']->name, 0, 12)))->getContent(), 'onrequest', 'search suggestions');
    }
}
