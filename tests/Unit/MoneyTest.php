<?php

namespace Tests\Unit;

use App\Pricing\QuoteTotals;
use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_parses_decimal_strings_without_floats(): void
    {
        $this->assertSame(1250, Money::parse('12.50'));
        $this->assertSame(1250, Money::parse('12.5'));
        $this->assertSame(1800, Money::parse('$18'));
        $this->assertSame(123456, Money::parse('1,234.56'));
        $this->assertSame(1, Money::parse('0.01'));
        $this->assertNull(Money::parse(''));
    }

    public function test_rejects_ambiguous_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::parse('12.505');
    }

    public function test_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::parse('-1.00');
    }

    public function test_formats_cents(): void
    {
        $this->assertSame('$18.00', Money::format(1800));
        $this->assertSame('$1,234.05', Money::format(123405));
        $this->assertSame('—', Money::format(null));
        $this->assertSame('12.05', Money::toDecimalString(1205));
    }

    public function test_percentages_are_basis_points_with_half_up_rounding(): void
    {
        $this->assertSame(750, Money::parsePercentToBps('7.5'));
        $this->assertSame(1000, Money::parsePercentToBps('10'));
        $this->assertSame(8, Money::percentOf(105, 750));   // 7.875 -> 8
        $this->assertSame(0, Money::percentOf(6, 750));     // 0.45 -> 0
        $this->assertSame('7.5', Money::bpsToPercentString(750));
    }

    public function test_quote_totals_are_exact(): void
    {
        $totals = QuoteTotals::calculate([
            ['unit_cents' => 1250, 'quantity' => 150],                     // 187,500
            ['unit_cents' => 999, 'quantity' => 3, 'discount_bps' => 1000], // 2,997 - 300 = 2,697
        ], discountCents: 197, shippingCents: 5000, additionalCents: 1500, taxRateBps: 825);

        $this->assertSame(190197, $totals->subtotalCents);
        $this->assertSame(197, $totals->discountCents);
        // taxable = 190,000 + 1,500 + 5,000 = 196,500 → 8.25% = 16,211.25 → 16,211
        $this->assertSame(16211, $totals->taxCents);
        $this->assertSame(190000 + 5000 + 1500 + 16211, $totals->totalCents);
    }

    public function test_tax_can_exclude_shipping_and_discount_is_capped(): void
    {
        $totals = QuoteTotals::calculate([['unit_cents' => 1000, 'quantity' => 1]], discountCents: 5000, shippingCents: 1000, taxRateBps: 1000, taxOnShipping: false);
        $this->assertSame(1000, $totals->discountCents);
        $this->assertSame(0, $totals->taxCents);
        $this->assertSame(1000, $totals->totalCents);
    }
}
