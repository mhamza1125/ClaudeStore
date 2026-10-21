<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Pricing\PriceResolver;
use App\Pricing\PriceSource;

/** Price summaries for listing cards ("From $10.00"), respecting price visibility. */
class CatalogPricing
{
    public function __construct(private PriceResolver $resolver) {}

    /** @return array{label:string, cents:?int, note:?string} */
    public function summary(Product $product, ?Company $company): array
    {
        if ($product->price_on_request) {
            return ['label' => 'Price on request', 'cents' => null, 'note' => 'Quoted to specification'];
        }

        $atMoq = $this->resolver->resolve($product, null, max(1, $product->moq), $company);
        if (! $atMoq->hasPrice()) {
            return ['label' => 'Price on request', 'cents' => null, 'note' => null];
        }
        if (! $atMoq->visible) {
            return ['label' => 'Trade price', 'cents' => null, 'note' => 'Sign in with an approved trade account to view'];
        }

        $lowest = collect($atMoq->ladder)->min('unit_cents');
        $from = $lowest !== null ? min($lowest, $atMoq->unitCents) : $atMoq->unitCents;
        $customer = $atMoq->source->isCustomerSpecific() || $atMoq->source === PriceSource::NegotiatedProduct;

        return [
            'label' => $from < $atMoq->unitCents ? 'From' : 'Price',
            'cents' => $from,
            'note' => $customer ? 'Your account price' : null,
        ];
    }
}
