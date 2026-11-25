<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CompanyShow;
use App\Livewire\Admin\ProductEditor;
use App\Livewire\Admin\QuoteWorkspace;
use App\Models\Company;
use App\Models\CustomerPrice;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Services\QuoteBasketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * Audit F-04 / owner decision D4: removing a variant deactivates it and never deletes it or a
 * company's negotiated price for it. Inactive variants are ignored by the storefront, the
 * basket, staff quote lines and negotiated-price entry.
 */
class VariantRemovalTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create(['name' => 'Removal Wallet']);
        $this->variant = ProductVariant::factory()->for($this->product)->create(['sku' => 'VAR-NEGOTIATED', 'name' => 'Oxblood']);
        ProductVariant::factory()->for($this->product)->create(['sku' => 'VAR-STAYS', 'name' => 'Black']);
    }

    private function deactivateInEditor(): void
    {
        $this->actingAs($this->staff());
        Livewire::test(ProductEditor::class, ['product' => $this->product])
            ->call('removeVariant', 'id'.$this->variant->id)
            ->call('save')
            ->assertHasNoErrors();
        auth()->logout();
    }

    public function test_removing_a_variant_keeps_it_and_its_negotiated_prices(): void
    {
        $company = Company::factory()->approved()->create();
        $negotiated = CustomerPrice::create(['company_id' => $company->id, 'product_id' => $this->product->id, 'variant_id' => $this->variant->id, 'unit_price_cents' => 950]);
        $tier = PriceTier::create(['product_id' => $this->product->id, 'variant_id' => $this->variant->id, 'min_qty' => 10, 'unit_price_cents' => 1200]);

        $this->deactivateInEditor();

        $this->assertNotNull($this->variant->fresh(), 'the variant row must survive');
        $this->assertFalse($this->variant->fresh()->is_active, 'the variant must be inactive');
        $this->assertNotNull($negotiated->fresh(), 'the negotiated price must survive');
        $this->assertNotNull($tier->fresh(), 'the variant price tier must survive');
    }

    public function test_unsaved_new_variants_can_still_be_discarded(): void
    {
        $this->actingAs($this->staff());
        $c = Livewire::test(ProductEditor::class, ['product' => $this->product])->call('addVariant');
        $new = collect(array_keys($c->get('variants')))->first(fn ($k) => str_starts_with($k, 'new'));
        $c->call('removeVariant', $new)->call('save')->assertHasNoErrors();

        $this->assertSame(2, $this->product->variants()->count());
    }

    public function test_variants_cannot_be_deleted_through_the_model(): void
    {
        $this->expectException(LogicException::class);
        $this->variant->delete();
    }

    public function test_storefront_hides_an_inactive_variant(): void
    {
        $this->deactivateInEditor();

        $this->get(route('products.show', $this->product))->assertOk()->assertDontSee('Oxblood')->assertSee('Black');
        $this->get('/products?q=VAR-NEGOTIATED')->assertDontSee('Removal Wallet');
        $this->getJson('/search/suggest?q=VAR-NEGOTIATED')->assertJsonPath('total', 0);
        $this->get('/products?q=VAR-STAYS')->assertSee('Removal Wallet');
    }

    public function test_basket_refuses_and_blocks_submission_of_an_inactive_variant(): void
    {
        // Added while active, then deactivated before submission.
        app(QuoteBasketService::class)->add($this->product, $this->variant, 1);
        $this->deactivateInEditor();

        $this->post('/quote', $this->quoteForm())->assertSessionHasErrors('items');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(QuoteBasketService::class)->add($this->product, $this->variant->fresh(), 1);
    }

    public function test_staff_cannot_add_an_inactive_variant_to_a_quote_or_a_negotiated_price(): void
    {
        $this->deactivateInEditor();
        $this->actingAs($this->staff());

        $quote = Quote::factory()->create();
        Livewire::test(QuoteWorkspace::class, ['quote' => $quote])
            ->set('newSku', 'VAR-NEGOTIATED')->set('newQty', 1)->call('addLine')->assertHasErrors('newSku');
        $this->assertSame(0, $quote->items()->count());

        $company = Company::factory()->approved()->create();
        Livewire::test(CompanyShow::class, ['company' => $company])
            ->set('price.sku', 'VAR-NEGOTIATED')->set('price.min_qty', 1)->set('price.unit_price', '9.00')->call('addPrice')->assertHasErrors('price.sku');
        $this->assertSame(0, CustomerPrice::query()->where('company_id', $company->id)->count());
    }
}
