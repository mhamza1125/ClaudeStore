<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Services\ProductCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Characterization (audit §8.2): every spreadsheet formula trigger is neutralised on export.
 * Extends the pre-existing "=" case in ProductCsvTest to all trigger characters.
 */
class ProductCsvEscapingTest extends TestCase
{
    use RefreshDatabase;

    public static function triggers(): array
    {
        return ['equals' => ['=SUM(A1)'], 'plus' => ['+CMD'], 'minus' => ['-2+3'], 'at' => ['@SUM(1)'], 'tab' => ["\t=1"], 'carriage return' => ["\r=1"]];
    }

    #[DataProvider('triggers')]
    public function test_cells_starting_with_a_formula_trigger_are_prefixed(string $value): void
    {
        $this->assertSame("'".$value, app(ProductCsv::class)->escapeCell($value));
    }

    public function test_safe_values_are_unchanged(): void
    {
        $csv = app(ProductCsv::class);
        foreach (['Wallet', '12.50', '', 'a=b', 'Bag - large'] as $value) {
            $this->assertSame($value, $csv->escapeCell($value));
        }
        $this->assertSame(25, $csv->escapeCell(25));
    }

    public function test_exported_file_escapes_customer_visible_text_columns(): void
    {
        Product::factory()->create(['sku' => 'ESC-1', 'name' => 'Safe name', 'short_description' => '+HYPERLINK("x")', 'material' => '@evil']);

        $this->actingAs($this->staff());
        $content = $this->get('/admin/products-export.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString("'+HYPERLINK", $content);
        $this->assertStringContainsString("'@evil", $content);
        $this->assertStringNotContainsString(',+HYPERLINK', $content);
    }
}
