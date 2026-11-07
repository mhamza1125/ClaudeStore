<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ProductIndex;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_publish_sets_status_and_published_at_and_audits(): void
    {
        $this->actingAs($this->staff());
        [$a, $b] = Product::factory()->draft()->count(2)->create();
        $untouched = Product::factory()->draft()->create();

        Livewire::test(ProductIndex::class)
            ->set('selected', [(string) $a->id, (string) $b->id])
            ->set('bulkAction', 'publish')
            ->call('applyBulk')
            ->assertHasNoErrors()
            ->assertSet('selected', [])
            ->assertSee('2 of 2 selected products updated');

        foreach ([$a, $b] as $p) {
            $p->refresh();
            $this->assertSame('published', $p->status);
            $this->assertNotNull($p->published_at);
        }
        $this->assertSame('draft', $untouched->refresh()->status);
        $this->assertSame(2, AuditLog::query()->where('action', 'product.bulk')->count());
    }

    public function test_bulk_move_to_category_requires_a_category(): void
    {
        $this->actingAs($this->staff());
        $p = Product::factory()->create();
        $target = Category::factory()->create();

        Livewire::test(ProductIndex::class)
            ->set('selected', [(string) $p->id])->set('bulkAction', 'category')
            ->call('applyBulk')->assertHasErrors('bulkCategory')
            ->set('bulkCategory', (string) $target->id)
            ->call('applyBulk')->assertHasNoErrors();

        $this->assertSame($target->id, $p->refresh()->category_id);
    }

    public function test_bulk_raw_leather_forces_price_on_request_and_quotes(): void
    {
        $this->actingAs($this->staff());
        $p = Product::factory()->create(['price_on_request' => false, 'quote_enabled' => false]);

        Livewire::test(ProductIndex::class)
            ->set('selected', [(string) $p->id])->set('bulkAction', 'raw_leather')->call('applyBulk');

        $p->refresh();
        $this->assertTrue($p->is_raw_leather);
        $this->assertTrue($p->price_on_request);
        $this->assertTrue($p->quote_enabled);

        // Prices cannot be switched back on while the product is raw leather.
        Livewire::test(ProductIndex::class)
            ->set('selected', [(string) $p->id])->set('bulkAction', 'show_price')->call('applyBulk');
        $this->assertTrue($p->refresh()->price_on_request);
    }

    public function test_unknown_action_and_empty_selection_are_rejected(): void
    {
        $this->actingAs($this->staff());
        $p = Product::factory()->create();

        Livewire::test(ProductIndex::class)->set('bulkAction', 'publish')->call('applyBulk')->assertHasErrors('bulkAction');
        Livewire::test(ProductIndex::class)->set('selected', [(string) $p->id])->set('bulkAction', 'delete_everything')
            ->call('applyBulk')->assertHasErrors('bulkAction');
    }

    public function test_select_page_and_select_all_matching_respect_filters(): void
    {
        $this->actingAs($this->staff());
        $raw = Product::factory()->count(2)->create(['is_raw_leather' => true]);
        Product::factory()->count(3)->create();

        $c = Livewire::test(ProductIndex::class)->set('type', 'raw')->call('selectAllMatching');
        $this->assertEqualsCanonicalizing($raw->pluck('id')->map(fn ($id) => (string) $id)->all(), $c->get('selected'));

        // Changing a filter drops the selection.
        $c->set('type', '')->assertSet('selected', []);

        $ids = Product::query()->pluck('id')->all();
        $c->call('togglePage', $ids)->assertCount('selected', 5)->call('togglePage', $ids)->assertSet('selected', []);
    }

    public function test_sales_staff_cannot_reach_products_admin(): void
    {
        $this->actingAs($this->staff('sales-rep'))->get('/admin/products')->assertForbidden();
    }

    public function test_homepage_shows_raw_leather_gallery_and_catalogue_filter(): void
    {
        Product::factory()->create(['name' => 'Full Grain Buffalo Hide', 'is_raw_leather' => true, 'leather_type' => 'Buffalo']);
        Product::factory()->create(['name' => 'Classic Bifold Wallet']);

        $this->get('/')->assertOk()->assertSee('data-testid="raw-leather-gallery"', false)
            ->assertSee('Full Grain Buffalo Hide')->assertSee('Request a quote');

        $this->get('/products?raw=1')->assertOk()->assertSee('Full Grain Buffalo Hide')->assertDontSee('Classic Bifold Wallet');
    }

    public function test_homepage_hides_gallery_without_raw_leather(): void
    {
        Product::factory()->create();

        $this->get('/')->assertOk()->assertDontSee('data-testid="raw-leather-gallery"', false);
    }
}
