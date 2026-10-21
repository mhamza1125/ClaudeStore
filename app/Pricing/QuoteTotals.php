<?php

namespace App\Pricing;

use App\Support\Money;

/**
 * Integer-only totals for quotations and orders.
 *
 * line_total = unit × qty − round(unit × qty × line_discount_bps / 10000)
 * subtotal   = Σ line_total
 * taxable    = subtotal − order_discount + additional charges (+ shipping when tax applies to shipping)
 * total      = subtotal − order_discount + shipping + additional + tax
 */
final class QuoteTotals
{
    public function __construct(
        public readonly int $subtotalCents,
        public readonly int $discountCents,
        public readonly int $shippingCents,
        public readonly int $additionalCents,
        public readonly int $taxRateBps,
        public readonly int $taxCents,
        public readonly int $totalCents,
    ) {}

    public static function lineTotal(int $unitCents, int $quantity, int $discountBps = 0): int
    {
        $gross = $unitCents * $quantity;

        return $gross - Money::percentOf($gross, $discountBps);
    }

    /**
     * @param  iterable<array{unit_cents:int, quantity:int, discount_bps?:int}>  $lines
     */
    public static function calculate(iterable $lines, int $discountCents = 0, int $shippingCents = 0, int $additionalCents = 0, int $taxRateBps = 0, bool $taxOnShipping = true): self
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += self::lineTotal($line['unit_cents'], $line['quantity'], $line['discount_bps'] ?? 0);
        }
        $discountCents = min(max(0, $discountCents), $subtotal);
        $taxable = $subtotal - $discountCents + $additionalCents + ($taxOnShipping ? $shippingCents : 0);
        $tax = Money::percentOf($taxable, $taxRateBps);
        $total = $subtotal - $discountCents + $shippingCents + $additionalCents + $tax;

        return new self($subtotal, $discountCents, $shippingCents, $additionalCents, $taxRateBps, $tax, $total);
    }
}
