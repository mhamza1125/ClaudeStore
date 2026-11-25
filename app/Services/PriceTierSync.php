<?php

namespace App\Services;

use App\Models\PriceTier;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * Brings a product's price tiers to a desired set without delete-and-recreate (audit F-04).
 *
 * Tiers are keyed by (variant, customer group, min_qty). A tier present in both sets keeps its
 * row (its price is updated if it changed); a new key is created; only a key the user removed
 * is deleted. Callers record the before/after in the audit log.
 */
class PriceTierSync
{
    /**
     * @param  array<int, array{variant_id:?int, customer_group_id:?int, min_qty:int, unit_price_cents:int}>  $desired
     * @param  ?callable(Builder): void  $scope  limits which existing tiers take part (e.g. CSV: product-level standard tiers only)
     */
    public function sync(Product $product, array $desired, ?callable $scope = null): void
    {
        $existing = $product->priceTiers()->when($scope, fn (Builder $q) => $scope($q))->get()->keyBy(fn (PriceTier $t) => $this->key($t->variant_id, $t->customer_group_id, $t->min_qty));

        $wanted = [];
        foreach ($desired as $row) {
            $key = $this->key($row['variant_id'], $row['customer_group_id'], (int) $row['min_qty']);
            $wanted[$key] = true;
            $tier = $existing->get($key);
            if ($tier) {
                if ($tier->unit_price_cents !== (int) $row['unit_price_cents']) {
                    $tier->update(['unit_price_cents' => (int) $row['unit_price_cents']]);
                }

                continue;
            }
            $product->priceTiers()->create($row);
        }

        // Only tiers the user removed from the set are deleted.
        foreach ($existing as $key => $tier) {
            if (! isset($wanted[$key])) {
                $tier->delete();
            }
        }
    }

    /** Ids may arrive as int or numeric string depending on the PDO driver. */
    private function key(int|string|null $variantId, int|string|null $groupId, int|string $minQty): string
    {
        return ($variantId === null ? '-' : (int) $variantId).'|'.($groupId === null ? '-' : (int) $groupId).'|'.(int) $minQty;
    }
}
