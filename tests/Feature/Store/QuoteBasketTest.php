<?php

namespace Tests\Feature\Store;

use App\Livewire\QuoteBasketCount;
use App\Livewire\Store\Catalog;
use App\Livewire\Store\ProductPurchase;
use App\Livewire\Store\QuoteBasketPage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuoteBasket;
use App\Services\QuoteBasketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteBasketTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_to_quote_enforces_moq_and_multiples_server_side(): void
    {
        $belt = Product::factory()->create(['moq' => 12, 'order_multiple' => 6]);

        Livewire::test(ProductPurchase::class, ['product' => $belt])
            ->set('quantity', 6)->call('addToQuote')->assertHasErrors('quantity')
            ->set('quantity', 13)->call('addToQuote')->assertHasErrors('quantity')
            ->set('quantity', 18)->call('addToQuote')->assertHasNoErrors()->assertDispatched('basket-updated');

        $this->assertSame(18, app(QuoteBasketService::class)->lines()->first()['item']->quantity);
    }

    public function test_variant_is_required_when_product_has_variants(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->count(2)->create();
        Livewire::test(ProductPurchase::class, ['product' => $product])->call('addToQuote')->assertHasErrors('variant');

        $foreign = ProductVariant::factory()->create();
        Livewire::test(ProductPurchase::class, ['product' => $product])->set('variantId', $foreign->id)->call('addToQuote')->assertHasErrors('variant');
    }

    public function test_basket_update_remove_and_count(): void
    {
        $a = Product::factory()->create(['name' => 'Alpha']);
        $b = Product::factory()->create(['name' => 'Bravo', 'moq' => 5]);
        $basket = app(QuoteBasketService::class);
        $basket->add($a, null, 3, 'Embossed logo');
        $item = $basket->add($b, null, 5);

        Livewire::test(QuoteBasketCount::class)->assertSee('2');
        $page = Livewire::test(QuoteBasketPage::class)->assertSee('Alpha')->assertSee('Bravo')
            ->assertSet('edits.'.$basket->lines()->first()['item']->id.'.customization', 'Embossed logo');

        $page->set("edits.{$item->id}.quantity", 2)->call('save', $item->id)->assertHasErrors("edits.{$item->id}.quantity");
        $page->set("edits.{$item->id}.quantity", 10)->call('save', $item->id)->assertHasNoErrors();
        $this->assertSame(10, $item->fresh()->quantity);

        $page->call('remove', $item->id)->assertDontSee('Bravo');
        $this->assertSame(1, $basket->count());
    }

    public function test_adding_same_product_twice_merges_quantities(): void
    {
        $p = Product::factory()->create();
        $basket = app(QuoteBasketService::class);
        $basket->add($p, null, 3);
        $basket->add($p, null, 4);
        $this->assertSame(1, $basket->count());
        $this->assertSame(7, $basket->lines()->first()['item']->quantity);
    }

    public function test_unpublished_or_quote_disabled_products_cannot_be_added(): void
    {
        $this->expectException(ValidationException::class);
        app(QuoteBasketService::class)->add(Product::factory()->create(['quote_enabled' => false]), null, 1);
    }

    public function test_guest_basket_is_merged_into_user_basket_on_login(): void
    {
        $p = Product::factory()->create();
        app(QuoteBasketService::class)->add($p, null, 4);
        $user = $this->customer();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertSame(4, QuoteBasket::query()->where('user_id', $user->id)->first()->items()->first()->quantity);
    }

    public function test_bulk_add_from_catalog_list_view(): void
    {
        $a = Product::factory()->create(['name' => 'Alpha']);
        $b = Product::factory()->create(['name' => 'Bravo', 'moq' => 50]);

        Livewire::test(Catalog::class)->set('view', 'list')
            ->set("selected.{$a->id}", true)->set("quantities.{$a->id}", 10)
            ->set("selected.{$b->id}", true)->set("quantities.{$b->id}", 10)
            ->call('addSelected')
            ->assertSee('1 product added')
            ->assertSee('The minimum order quantity is 50.');

        $this->assertSame(1, app(QuoteBasketService::class)->count());
    }

    public function test_basket_shows_indicative_total_from_server_prices(): void
    {
        $p = Product::factory()->create(['base_price_cents' => 1250]);
        app(QuoteBasketService::class)->add($p, null, 4);
        Livewire::test(QuoteBasketPage::class)->assertSeeHtml('data-testid="basket-total">$50.00');
    }
}
