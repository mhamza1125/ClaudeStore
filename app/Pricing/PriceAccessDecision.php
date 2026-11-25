<?php

namespace App\Pricing;

use App\Models\Company;

/**
 * Whether one viewer may see catalogue prices, and why not. Produced by PriceAccess and
 * passed to PriceResolver so visibility is decided on the server for every price shown.
 */
final class PriceAccessDecision
{
    public const OK = 'ok';

    public const GUEST = 'guest';           // sign in / register to see prices

    public const VERIFY = 'verify';         // verify the account's email address

    public const APPROVAL = 'approval';     // awaiting administrator approval

    public const REJECTED = 'rejected';     // approval declined

    public const RESTRICTED = 'restricted'; // suspended account or individual "never show prices"

    /**
     * @param  ?Company  $company  buyer company for group/negotiated prices (null unless allowed)
     * @param  bool  $tradeCatalogue  B2B access also unlocks products marked "trade only"
     */
    public function __construct(
        public readonly bool $allowed,
        public readonly string $reason = self::OK,
        public readonly ?Company $company = null,
        public readonly bool $tradeCatalogue = false,
    ) {}

    public static function denied(string $reason): self
    {
        return new self(false, $reason);
    }

    /** Short label used where a price would be (cards, search suggestions). */
    public function label(): string
    {
        return match ($this->reason) {
            self::GUEST => 'Sign in for price',
            self::VERIFY => 'Verify email for price',
            self::APPROVAL => 'Price after approval',
            default => 'Price on request',
        };
    }
}
