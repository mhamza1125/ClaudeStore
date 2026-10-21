<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\ProductEditor;
use App\Livewire\Admin\SettingsForm;
use App\Livewire\Admin\StaffManager;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Pricing\PriceResolver;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_product_with_variants_and_tiers(): void
    {
        $this->actingAs($this->staff());
        $category = Category::factory()->create();
        $group = CustomerGroup::query()->where('slug', 'wholesale')->first();

        $c = Livewire::test(ProductEditor::class)
            ->set('form.name', 'Card Case')
            ->set('form.sku', 'AH-NEW-001')
            ->set('form.category_id', (string) $category->id)
            ->set('form.base_price', '20.00')
            ->set('form.moq', 10)
            ->set('form.status', 'published')
            ->call('addVariant')->call('addVariant');
        $keys = array_keys($c->get('variants'));
        $c->set("variants.{$keys[0]}.name", 'Black')->set("variants.{$keys[0]}.sku", 'AH-NEW-001-BLK')->set("variants.{$keys[0]}.color", 'Black')
            ->set("variants.{$keys[1]}.name", 'Tan')->set("variants.{$keys[1]}.sku", 'AH-NEW-001-TAN')->set("variants.{$keys[1]}.price", '22.50')
            ->call('addTier')->set('tiers.0.min_qty', 10)->set('tiers.0.price', '18.00')
            ->call('addTier')->set('tiers.1.min_qty', 100)->set('tiers.1.price', '15.00')
            ->call('addTier')->set('tiers.2.min_qty', 10)->set('tiers.2.price', '17.00')->set('tiers.2.variant', $keys[1])
            ->call('addTier')->set('tiers.3.min_qty', 10)->set('tiers.3.price', '14.00')->set('tiers.3.group', (string) $group->id)
            ->call('save')->assertHasNoErrors()->assertRedirect();

        $p = Product::query()->where('sku', 'AH-NEW-001')->with(['variants', 'priceTiers'])->sole();
        $this->assertSame('card-case', $p->slug);
        $this->assertSame(2000, $p->base_price_cents);
        $this->assertNotNull($p->published_at);
        $this->assertCount(2, $p->variants);
        $this->assertCount(4, $p->priceTiers);
        $tan = $p->variants->firstWhere('sku', 'AH-NEW-001-TAN');
        $this->assertSame(2250, $tan->price_cents);

        $resolver = new PriceResolver;
        $this->assertSame(1500, $resolver->resolve($p, $p->variants->firstWhere('sku', 'AH-NEW-001-BLK'), 150)->unitCents);
        $this->assertSame(1700, $resolver->resolve($p, $tan, 150)->unitCents, 'variant tiers override product tiers');
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.created']);
    }

    public function test_sku_uniqueness_across_products_and_variants(): void
    {
        $this->actingAs($this->staff());
        $existing = Product::factory()->create(['sku' => 'TAKEN-1']);
        ProductVariant::factory()->for($existing)->create(['sku' => 'TAKEN-2']);

        Livewire::test(ProductEditor::class)->set('form.name', 'X')->set('form.sku', 'TAKEN-1')->call('save')->assertHasErrors('form.sku');
        Livewire::test(ProductEditor::class)->set('form.name', 'X')->set('form.sku', 'TAKEN-2')->call('save')->assertHasErrors('form.sku');

        $c = Livewire::test(ProductEditor::class)->set('form.name', 'X')->set('form.sku', 'FREE-1')->call('addVariant');
        $key = array_key_first($c->get('variants'));
        $c->set("variants.$key.name", 'V')->set("variants.$key.sku", 'TAKEN-2')->call('save')->assertHasErrors("variants.$key.sku");
    }

    public function test_editing_keeps_variant_ids_and_audits_price_changes(): void
    {
        $this->actingAs($this->staff());
        $p = Product::factory()->create(['base_price_cents' => 1000]);
        $v = ProductVariant::factory()->for($p)->create(['sku' => 'KEEP-1']);

        Livewire::test(ProductEditor::class, ['product' => $p])->set('form.base_price', '11.00')->call('save')->assertHasNoErrors();
        $this->assertSame(1100, $p->fresh()->base_price_cents);
        $this->assertSame($v->id, $p->variants()->sole()->id);
        $log = \App\Models\AuditLog::query()->where('action', 'product.updated')->latest('id')->first();
        $this->assertSame(['from' => 1000, 'to' => 1100], $log->changes['base_price_cents']);
    }

    public function test_duplicate_tier_thresholds_are_rejected(): void
    {
        $this->actingAs($this->staff());
        $p = Product::factory()->create();
        Livewire::test(ProductEditor::class, ['product' => $p])
            ->call('addTier')->set('tiers.0.min_qty', 10)->set('tiers.0.price', '5.00')
            ->call('addTier')->set('tiers.1.min_qty', 10)->set('tiers.1.price', '4.00')
            ->call('save')->assertHasErrors('tiers.1.min_qty');
    }

    public function test_duplicate_archive_and_publish(): void
    {
        $this->actingAs($this->staff());
        $p = Product::factory()->create(['sku' => 'ORIG']);
        ProductVariant::factory()->for($p)->create(['sku' => 'ORIG-V']);

        Livewire::test(ProductEditor::class, ['product' => $p])->call('duplicate')->assertRedirect();
        $copy = Product::query()->where('sku', 'like', 'ORIG-COPY-%')->sole();
        $this->assertSame('draft', $copy->status);
        $this->assertCount(1, $copy->variants);

        Livewire::test(ProductEditor::class, ['product' => $p])->call('setStatus', 'archived');
        $this->assertSame('archived', $p->fresh()->status);
        $this->get('/products/'.$p->slug)->assertOk(); // staff preview
        auth()->logout();
        $this->get('/products/'.$p->slug)->assertNotFound();
    }

    public function test_category_tree_is_limited_to_two_levels(): void
    {
        $this->actingAs($this->staff());
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);
        Livewire::test(CategoryManager::class)->set('form.name', 'Grandchild')->set('form.parent_id', (string) $child->id)->call('save')->assertHasErrors('form.parent_id');
        Livewire::test(CategoryManager::class)->set('form.name', 'Sub')->set('form.parent_id', (string) $root->id)->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('categories', ['name' => 'Sub', 'slug' => 'sub', 'parent_id' => $root->id]);
    }

    public function test_settings_update_drives_quote_defaults(): void
    {
        $this->actingAs($this->staff());
        Livewire::test(SettingsForm::class)
            ->set('s.brand_name', 'Hide & Co')->set('s.tax_rate', '7.5')->set('s.shipping_flat', '30.00')
            ->set('testimonials', "Great quality | Sam | Shop Ltd")
            ->call('save')->assertHasNoErrors();
        $settings = app(Settings::class);
        $settings->flush();
        $this->assertSame(750, $settings->get('tax.rate_bps'));
        $this->assertSame(3000, $settings->get('shipping.flat_cents'));
        $this->get('/')->assertSee('Hide &amp; Co', false)->assertSee('Great quality');
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_super_admin_creates_staff_and_cannot_demote_self(): void
    {
        $super = $this->staff('super-admin');
        $this->actingAs($super);
        Livewire::test(StaffManager::class)->set('form.name', 'Rep')->set('form.email', 'rep@x.test')->set('form.role', 'sales-rep')->call('create')->assertHasNoErrors();
        $this->assertTrue(User::query()->where('email', 'rep@x.test')->sole()->hasRole('sales-rep'));
        Livewire::test(StaffManager::class)->call('setRole', $super->id, 'customer')->assertStatus(422);
    }
}
