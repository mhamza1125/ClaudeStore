<?php

namespace Tests\Feature\B2b;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Pricing\PriceAccess;
use App\Pricing\PriceResolver;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Documents who sees which list price under every combination of Admin → B2B settings
 * (owner decision D3: behaviour unchanged, documented in docs/B2B.md and docs/PRICING.md).
 *
 * "public" = a product with public pricing; "trade" = a product marked trade only
 * (public_pricing = false). Price-on-request products never show a price to anyone.
 */
class PriceVisibilityMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * settings => [b2b.enabled, require_registration, guest_prices, require_email_verification, require_approval]
     * expected => viewer => [sees public, sees trade]
     *   guest: not signed in
     *   registrant: signed-in customer, email NOT verified, approval pending, no company
     *   wholesale: verified, approved, member of a wholesale-approved company
     */
    public static function combinations(): array
    {
        return [
            'B2B off' => [[false, true, true, false, false],
                ['guest' => [true, false], 'registrant' => [true, false], 'wholesale' => [true, true]]],
            'B2B on, registration required, instant access (default)' => [[true, true, true, false, false],
                ['guest' => [false, false], 'registrant' => [true, true], 'wholesale' => [true, true]]],
            'B2B on, registration required, verify email' => [[true, true, true, true, false],
                ['guest' => [false, false], 'registrant' => [false, false], 'wholesale' => [true, true]]],
            'B2B on, registration required, approval' => [[true, true, true, false, true],
                ['guest' => [false, false], 'registrant' => [false, false], 'wholesale' => [true, true]]],
            'B2B on, registration required, verify and approval' => [[true, true, true, true, true],
                ['guest' => [false, false], 'registrant' => [false, false], 'wholesale' => [true, true]]],
            'B2B on, registration not required, guest prices on' => [[true, false, true, false, false],
                ['guest' => [true, false], 'registrant' => [true, true], 'wholesale' => [true, true]]],
            'B2B on, registration not required, guest prices off' => [[true, false, false, false, false],
                ['guest' => [false, false], 'registrant' => [true, true], 'wholesale' => [true, true]]],
            // Verification/approval only gate prices while registration is required.
            'B2B on, registration not required, verify and approval ticked' => [[true, false, true, true, true],
                ['guest' => [true, false], 'registrant' => [true, true], 'wholesale' => [true, true]]],
        ];
    }

    #[DataProvider('combinations')]
    public function test_who_sees_public_and_trade_only_list_prices(array $settings, array $expected): void
    {
        [$enabled, $registration, $guestPrices, $verify, $approval] = $settings;
        app(Settings::class)->set([
            'b2b.enabled' => $enabled, 'b2b.require_registration' => $registration, 'b2b.guest_prices' => $guestPrices,
            'b2b.require_email_verification' => $verify, 'b2b.require_approval' => $approval,
        ]);

        $public = Product::factory()->create(['public_pricing' => true, 'base_price_cents' => 1000]);
        $trade = Product::factory()->create(['public_pricing' => false, 'base_price_cents' => 2000]);
        $onRequest = Product::factory()->create(['price_on_request' => true, 'base_price_cents' => 3000]);

        $viewers = [
            'guest' => null,
            'registrant' => tap($this->customer(), fn (User $u) => $u->forceFill(['email_verified_at' => null, 'approval_status' => 'pending', 'approved_at' => null])->save()),
            'wholesale' => tap($this->customer(Company::factory()->approved()->create()), fn (User $u) => $u->forceFill(['email_verified_at' => now(), 'approval_status' => 'approved', 'approved_at' => now()])->save()),
        ];

        $resolver = app(PriceResolver::class);
        foreach ($viewers as $name => $user) {
            $access = app(PriceAccess::class)->for($user?->fresh());
            $company = $access->company?->loadMissing('customerGroup');
            $actual = [
                $resolver->resolve($public, null, 1, $company, access: $access)->displayUnitCents() !== null,
                $resolver->resolve($trade, null, 1, $company, access: $access)->displayUnitCents() !== null,
            ];
            $this->assertSame($expected[$name], $actual, "{$name}: [public, trade] visibility");
            $this->assertNull($resolver->resolve($onRequest, null, 1, $company, access: $access)->displayUnitCents(), "{$name}: on-request price must stay hidden");
        }
    }
}
