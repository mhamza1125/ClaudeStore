<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Product;
use App\Models\Quote;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoCustomerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_page_renders_for_super_admin_with_demo_data(): void
    {
        Storage::fake('public');
        $this->seed([DemoCatalogSeeder::class, DemoCustomerSeeder::class]);
        $quote = Quote::factory()->create();
        $quote->items()->create(['sku' => 'X', 'product_name' => 'Y', 'quantity' => 1]);
        $product = Product::query()->first();
        $company = Company::query()->first();

        $this->actingAs($this->staff('super-admin'));
        foreach (['/admin', '/admin/quotes', '/admin/quotes/'.$quote->reference, '/admin/quotes/'.$quote->reference.'/pdf', '/admin/orders',
            '/admin/products', '/admin/products/create', '/admin/products/'.$product->id.'/edit', '/admin/categories', '/admin/import-export',
            '/admin/customers', '/admin/customers/'.$company->id, '/admin/customer-groups', '/admin/settings', '/admin/staff', '/admin/audit-log'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_header_shows_the_role_in_title_case(): void
    {
        $this->actingAs($this->staff('super-admin'));
        $this->get('/admin')->assertOk()->assertSee('Super Admin')->assertDontSee('super-admin</span>', false);
    }

    public function test_demo_seed_produces_the_illustrative_wallet_tiers(): void
    {
        Storage::fake('public');
        $this->seed([DemoCatalogSeeder::class]);
        $wallet = Product::query()->where('sku', 'AH-WAL-001')->sole();
        $this->assertSame([1 => 1800, 25 => 1500, 100 => 1250, 250 => 1000],
            $wallet->priceTiers()->whereNull('customer_group_id')->pluck('unit_price_cents', 'min_qty')->all());
        $this->assertTrue($wallet->is_demo);
        Storage::disk('public')->assertExists('demo/products/classic-bifold-wallet-front.svg');
    }
}
