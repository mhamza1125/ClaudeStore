<?php

namespace App\Pricing;

use App\Models\Company;
use App\Models\CustomerPrice;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\BusinessTime;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The single source of truth for unit prices. See docs/PRICING.md.
 *
 * Precedence (first match wins):
 *   1. Company negotiated price — variant row, then product row (approved wholesale companies only)
 *   2. Customer-group tier price — variant tiers, then product tiers (approved wholesale companies only)
 *   3. Standard quantity tiers — variant tiers override product tiers
 *   4. Variant base price, then product base price
 * A customer group's optional discount (basis points) applies only to levels 3 and 4.
 *
 * Storefront callers pass the viewer's PriceAccessDecision: when it denies access no price is
 * visible and account pricing is not applied. Without one (staff tools) visibility follows the
 * product's public pricing flag and wholesale approval only.
 */
class PriceResolver
{
    public function resolve(Product $product, ?ProductVariant $variant, int $quantity, ?Company $company = null, ?Carbon $on = null, ?PriceAccessDecision $access = null): PriceResult
    {
        $on ??= now();
        $quantity = max(0, $quantity);
        if ($variant && $variant->product_id !== $product->id) {
            throw new \InvalidArgumentException('Variant does not belong to product.');
        }

        $moq = max(1, (int) ($variant?->moq ?? $product->moq ?? 1));
        $multiple = max(1, (int) ($product->order_multiple ?? 1));
        $errors = $this->quantityErrors($quantity, $moq, $multiple);
        $allowed = $access?->allowed ?? true;
        $eligible = $allowed && $company !== null && $company->isWholesaleApproved();

        if ($product->price_on_request) {
            return new PriceResult(null, PriceSource::OnRequest, false, $quantity, $moq, $multiple, errors: $errors);
        }

        $visible = $allowed && ($product->public_pricing || $eligible || ($access?->tradeCatalogue ?? false));
        $tiers = $product->relationLoaded('priceTiers') ? $product->priceTiers : $product->priceTiers()->get();
        $groupId = $eligible ? $company->customer_group_id : null;
        $groupDiscount = $eligible ? (int) ($company->customerGroup?->discount_bps ?? 0) : 0;

        // 1. Negotiated company price
        if ($eligible) {
            $negotiated = $this->negotiated($company, $product, $variant, $quantity, $on);
            if ($negotiated) {
                [$price, $source] = $negotiated;

                // The ladder shows what the buyer would pay without the negotiated price: group
                // tiers at their own prices, or standard tiers with the group discount (audit F-20).
                [$groupLadder] = $groupId ? $this->pickTierSet($tiers->where('customer_group_id', $groupId), $variant) : [collect()];
                $ladder = $groupLadder->isNotEmpty()
                    ? $this->ladder($groupLadder, $quantity)
                    : $this->ladder($this->pickTierSet($tiers->whereNull('customer_group_id'), $variant)[0], $quantity, $groupDiscount);

                return new PriceResult(
                    $price->unit_price_cents, $source, true, $quantity, $moq, $multiple,
                    tierMinQty: $price->min_qty,
                    ladder: $ladder,
                    errors: $errors,
                );
            }
        }

        // 2. Customer-group tiers
        if ($groupId) {
            [$groupTiers, $isVariant] = $this->pickTierSet($tiers->where('customer_group_id', $groupId), $variant);
            $match = $this->matchTier($groupTiers, $quantity);
            if ($match) {
                return new PriceResult(
                    $match->unit_price_cents,
                    $isVariant ? PriceSource::GroupTierVariant : PriceSource::GroupTierProduct,
                    true, $quantity, $moq, $multiple,
                    tierMinQty: $match->min_qty,
                    ladder: $this->ladder($groupTiers, $quantity),
                    errors: $errors,
                );
            }
        }

        // 3. Standard quantity tiers
        [$standard, $isVariant] = $this->pickTierSet($tiers->whereNull('customer_group_id'), $variant);
        $match = $this->matchTier($standard, $quantity);
        if ($match) {
            return new PriceResult(
                $this->discounted($match->unit_price_cents, $groupDiscount),
                $isVariant ? PriceSource::TierVariant : PriceSource::TierProduct,
                $visible, $quantity, $moq, $multiple,
                tierMinQty: $match->min_qty,
                ladder: $this->ladder($standard, $quantity, $groupDiscount),
                groupDiscountBps: $groupDiscount,
                errors: $errors,
            );
        }

        // 4. Base price
        $ladder = $this->ladder($standard, $quantity, $groupDiscount);
        if ($variant?->price_cents !== null) {
            return new PriceResult($this->discounted($variant->price_cents, $groupDiscount), PriceSource::BaseVariant, $visible, $quantity, $moq, $multiple, ladder: $ladder, groupDiscountBps: $groupDiscount, errors: $errors);
        }
        if ($product->base_price_cents !== null) {
            return new PriceResult($this->discounted($product->base_price_cents, $groupDiscount), PriceSource::BaseProduct, $visible, $quantity, $moq, $multiple, ladder: $ladder, groupDiscountBps: $groupDiscount, errors: $errors);
        }

        return new PriceResult(null, PriceSource::Unavailable, false, $quantity, $moq, $multiple, errors: $errors);
    }

    /** @return array<int,string> */
    public function quantityErrors(int $quantity, int $moq, int $multiple): array
    {
        $errors = [];
        if ($quantity < 1) {
            $errors[] = 'Quantity must be at least 1.';
        } elseif ($quantity < $moq) {
            $errors[] = "The minimum order quantity is {$moq}.";
        }
        if ($quantity > 0 && $quantity % $multiple !== 0) {
            $errors[] = "Please order in multiples of {$multiple}.";
        }

        return $errors;
    }

    private function negotiated(Company $company, Product $product, ?ProductVariant $variant, int $qty, Carbon $on): ?array
    {
        // valid_from / valid_until are business dates, inclusive (audit F-13).
        $day = BusinessTime::today($on);
        $rows = CustomerPrice::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('min_qty', '<=', max(1, $qty))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $day))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $day))
            ->orderByDesc('min_qty')
            ->get();

        if ($variant && ($row = $rows->firstWhere('variant_id', $variant->id))) {
            return [$row, PriceSource::NegotiatedVariant];
        }
        if ($row = $rows->whereNull('variant_id')->first()) {
            return [$row, PriceSource::NegotiatedProduct];
        }

        return null;
    }

    /**
     * Variant-specific tiers replace product tiers entirely when they exist.
     *
     * @return array{0: Collection<int,PriceTier>, 1: bool}
     */
    private function pickTierSet(Collection $tiers, ?ProductVariant $variant): array
    {
        if ($variant) {
            $variantTiers = $tiers->where('variant_id', $variant->id);
            if ($variantTiers->isNotEmpty()) {
                return [$variantTiers->sortBy('min_qty')->values(), true];
            }
        }

        return [$tiers->whereNull('variant_id')->sortBy('min_qty')->values(), false];
    }

    private function matchTier(Collection $tiers, int $qty): ?PriceTier
    {
        return $tiers->filter(fn (PriceTier $t) => $t->min_qty <= max(1, $qty))->sortByDesc('min_qty')->first();
    }

    private function discounted(int $cents, int $bps): int
    {
        return $bps > 0 ? $cents - Money::percentOf($cents, $bps) : $cents;
    }

    private function ladder(Collection $tiers, int $qty, int $discountBps = 0): array
    {
        $tiers = $tiers->sortBy('min_qty')->values();
        $active = $this->matchTier($tiers, $qty);

        return $tiers->map(fn (PriceTier $t, int $i) => [
            'min' => $t->min_qty,
            'max' => isset($tiers[$i + 1]) ? $tiers[$i + 1]->min_qty - 1 : null,
            'unit_cents' => $this->discounted($t->unit_price_cents, $discountBps),
            'active' => $active && $active->id === $t->id,
        ])->all();
    }
}
