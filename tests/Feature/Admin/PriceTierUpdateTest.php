<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ProductEditor;
use App\Models\AuditLog;
use App\Models\PriceTier;
use App\Models\Product;
use App\Services\ProductCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit F-04 (tiers): saving a product or importing a CSV updates price tiers in place. Only
 * tiers the user actually removed disappear; unchanged tiers keep their rows.
 */
class PriceTierUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    /** @var array<int, PriceTier> min_qty => tier */
    private array $tiers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create(['sku' => 'TIER-1']);
        foreach ([1 => 1800, 25 => 1500, 100 => 1250] as $min => $cents) {
            $this->tiers[$min] = PriceTier::create(['product_id' => $this->product->id, 'min_qty' => $min, 'unit_price_cents' => $cents]);
        }
    }

    public function test_editor_changes_and_removes_only_what_the_user_changed(): void
    {
        $this->actingAs($this->staff());
        $c = Livewire::test(ProductEditor::class, ['product' => $this->product]);
        $tiers = $c->get('tiers');
        $i25 = collect($tiers)->search(fn ($t) => (int) $t['min_qty'] === 25);
        $i100 = collect($tiers)->search(fn ($t) => (int) $t['min_qty'] === 100);
        $c->set("tiers.{$i25}.price", '14.00')->call('removeTier', $i100)->call('save')->assertHasNoErrors();

        $this->assertNotNull($this->tiers[1]->fresh(), 'unchanged tier keeps its row');
        $this->assertSame(1800, $this->tiers[1]->fresh()->unit_price_cents);
        $this->assertNotNull($this->tiers[25]->fresh(), 'changed tier is updated in place');
        $this->assertSame(1400, $this->tiers[25]->fresh()->unit_price_cents);
        $this->assertNull($this->tiers[100]->fresh(), 'the tier the user removed is gone');

        $log = AuditLog::query()->where('action', 'product.updated')->latest('id')->firstOrFail();
        $this->assertArrayHasKey('price_tiers', $log->changes, 'before/after kept in the audit log');
    }

    public function test_saving_without_tier_changes_keeps_every_tier_row(): void
    {
        $this->actingAs($this->staff());
        Livewire::test(ProductEditor::class, ['product' => $this->product])->set('form.name', 'Renamed')->call('save')->assertHasNoErrors();

        foreach ($this->tiers as $tier) {
            $this->assertNotNull($tier->fresh());
        }
    }

    public function test_csv_import_updates_tiers_in_place(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, "sku,name,price_tiers\nTIER-1,Tier product,1:18.00|25:13.50|250:9.00\n");

        $result = app(ProductCsv::class)->import($path);
        $this->assertSame([], $result['errors']);

        $this->assertNotNull($this->tiers[1]->fresh(), 'unchanged tier keeps its row');
        $this->assertSame(1350, $this->tiers[25]->fresh()?->unit_price_cents, 'changed tier updated in place');
        $this->assertNull($this->tiers[100]->fresh(), 'tier missing from the file is removed');
        $this->assertSame(900, $this->product->priceTiers()->where('min_qty', 250)->value('unit_price_cents'));
    }
}
