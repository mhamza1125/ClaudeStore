<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductCsvTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('products.csv', $content);
    }

    public function test_export_contains_tiers_and_escapes_formulas(): void
    {
        $p = Product::factory()->create(['sku' => 'EXP-1', 'name' => '=HYPERLINK("x")', 'base_price_cents' => 1800]);
        $p->priceTiers()->create(['min_qty' => 25, 'unit_price_cents' => 1500]);

        $this->actingAs($this->staff());
        $content = $this->get('/admin/products-export.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('EXP-1', $content);
        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString('25:15.00', $content);
        $this->assertStringContainsString('18.00', $content);
    }

    public function test_import_creates_and_updates_by_sku(): void
    {
        $cat = Category::factory()->create(['slug' => 'wallets']);
        Product::factory()->create(['sku' => 'UPD-1', 'name' => 'Old name', 'base_price_cents' => 100]);
        $this->actingAs($this->staff());

        $file = "sku,name,category_slug,status,base_price,moq,public_pricing,price_tiers\n"
            ."NEW-1,New Wallet,wallets,published,18.00,1,yes,1:18.00|25:15.00|100:12.50\n"
            ."UPD-1,New name,,,,,,\n";

        $this->post('/admin/products-import', ['file' => $this->csv($file), 'dry_run' => 1])->assertSessionHas('status');
        $this->assertDatabaseMissing('products', ['sku' => 'NEW-1']);

        $this->post('/admin/products-import', ['file' => $this->csv($file)])->assertSessionHas('status');
        $new = Product::query()->where('sku', 'NEW-1')->sole();
        $this->assertSame($cat->id, $new->category_id);
        $this->assertSame(1800, $new->base_price_cents);
        $this->assertSame([1 => 1800, 25 => 1500, 100 => 1250], $new->priceTiers()->pluck('unit_price_cents', 'min_qty')->all());
        $this->assertNotNull($new->published_at);
        $updated = Product::query()->where('sku', 'UPD-1')->sole();
        $this->assertSame('New name', $updated->name);
        $this->assertSame(100, $updated->base_price_cents, 'empty cells keep existing values');
    }

    public function test_invalid_rows_abort_the_whole_import(): void
    {
        $this->actingAs($this->staff());
        $file = "sku,name,base_price,price_tiers\nGOOD-1,Good,10.00,\nBAD-1,Bad,12.345,\nBAD-2,Bad tiers,1.00,abc\n";
        $this->post('/admin/products-import', ['file' => $this->csv($file)])->assertSessionHas('import_errors', fn ($e) => count($e) === 2);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_sales_rep_cannot_import(): void
    {
        $this->actingAs($this->staff('sales-rep'));
        $this->post('/admin/products-import', ['file' => $this->csv("sku,name\nX,Y\n")])->assertForbidden();
    }
}
