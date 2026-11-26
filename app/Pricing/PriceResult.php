<?php

namespace App\Pricing;

/**
 * Immutable outcome of a price resolution for one product/variant/quantity/buyer.
 */
final class PriceResult
{
    /**
     * @param  array<int, array{min:int, max:?int, unit_cents:int, active:bool}>  $ladder
     * @param  array<int, string>  $errors  quantity rule violations (MOQ, multiples)
     */
    public function __construct(
        public readonly ?int $unitCents,
        public readonly PriceSource $source,
        public readonly bool $visible,
        public readonly int $quantity,
        public readonly int $moq,
        public readonly int $orderMultiple,
        public readonly ?int $tierMinQty = null,
        public readonly array $ladder = [],
        public readonly int $groupDiscountBps = 0,
        public readonly array $errors = [],
    ) {}

    public function hasPrice(): bool
    {
        return $this->unitCents !== null;
    }

    /** Price that may be shown to the current viewer (null if hidden/unavailable). */
    public function displayUnitCents(): ?int
    {
        return $this->visible ? $this->unitCents : null;
    }

    public function lineTotalCents(): ?int
    {
        return $this->unitCents === null ? null : $this->unitCents * $this->quantity;
    }

    public function displayLineTotalCents(): ?int
    {
        return $this->visible ? $this->lineTotalCents() : null;
    }

    public function isValidQuantity(): bool
    {
        return $this->errors === [];
    }

    /** The next cheaper tier, for "order N more to save" hints. */
    public function nextTier(): ?array
    {
        foreach ($this->ladder as $row) {
            if ($row['min'] > $this->quantity) {
                return $row;
            }
        }

        return null;
    }
}
