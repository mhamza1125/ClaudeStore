<?php

namespace App\Pricing;

enum PriceSource: string
{
    case NegotiatedVariant = 'negotiated_variant';
    case NegotiatedProduct = 'negotiated_product';
    case GroupTierVariant = 'group_tier_variant';
    case GroupTierProduct = 'group_tier_product';
    case TierVariant = 'tier_variant';
    case TierProduct = 'tier_product';
    case BaseVariant = 'base_variant';
    case BaseProduct = 'base_product';
    case OnRequest = 'on_request';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::NegotiatedVariant, self::NegotiatedProduct => 'Your negotiated price',
            self::GroupTierVariant, self::GroupTierProduct => 'Your account price',
            self::TierVariant, self::TierProduct => 'Volume price',
            self::BaseVariant, self::BaseProduct => 'List price',
            self::OnRequest => 'Price on request',
            self::Unavailable => 'Price not set',
        };
    }

    public function isCustomerSpecific(): bool
    {
        return in_array($this, [self::NegotiatedVariant, self::NegotiatedProduct, self::GroupTierVariant, self::GroupTierProduct], true);
    }
}
