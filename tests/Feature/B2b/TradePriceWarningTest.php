<?php

namespace Tests\Feature\B2b;

use App\Livewire\Admin\B2bSettings;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Owner decision D3: behaviour unchanged, but Admin → B2B settings warns while instant access
 * (no verification, no approval) is combined with trade-only products, because then anyone who
 * registers sees trade prices. Viewing the page never changes stored settings.
 */
class TradePriceWarningTest extends TestCase
{
    use RefreshDatabase;

    private const WARNING = 'data-trade-price-warning';

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->staff('admin'));
    }

    public function test_warns_with_default_settings_when_trade_only_products_exist(): void
    {
        Product::factory()->create(['public_pricing' => false]);
        $this->get('/admin/b2b-settings')->assertOk()->assertSee(self::WARNING, false)->assertSee('anyone who registers');
    }

    public function test_no_warning_when_approval_is_required(): void
    {
        Product::factory()->create(['public_pricing' => false]);
        app(Settings::class)->set(['b2b.require_approval' => true]);
        $this->get('/admin/b2b-settings')->assertOk()->assertDontSee(self::WARNING, false);
    }

    public function test_no_warning_without_trade_only_products_or_with_b2b_off(): void
    {
        Product::factory()->create(['public_pricing' => true]);
        Product::factory()->create(['public_pricing' => false, 'price_on_request' => true]); // never shows a price
        $this->get('/admin/b2b-settings')->assertDontSee(self::WARNING, false);

        Product::factory()->create(['public_pricing' => false]);
        app(Settings::class)->set(['b2b.enabled' => false]);
        $this->get('/admin/b2b-settings')->assertDontSee(self::WARNING, false);
    }

    public function test_warning_follows_the_form_and_viewing_changes_no_stored_setting(): void
    {
        Product::factory()->create(['public_pricing' => false]);
        $before = Setting::query()->orderBy('key')->get(['key', 'value'])->toArray();

        Livewire::test(B2bSettings::class)->assertSee(self::WARNING, false)
            ->set('s.require_approval', true)->assertDontSee(self::WARNING, false);

        $this->assertSame($before, Setting::query()->orderBy('key')->get(['key', 'value'])->toArray(), 'nothing saved without pressing Save');
    }
}
