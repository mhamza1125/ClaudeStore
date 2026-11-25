<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\StaffManager;
use App\Models\Company;
use App\Models\CustomerGroup;
use App\Models\CustomerPrice;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ReferenceSequence;
use App\Models\User;
use App\Pricing\PriceResolver;
use App\Services\ReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/** Audit low-risk batch: F-17, F-20, F-25, F-26, F-28. */
class LowRiskHardeningTest extends TestCase
{
    use RefreshDatabase;

    // F-17 -------------------------------------------------------------------------------

    public function test_password_reset_request_gives_the_same_answer_for_unknown_and_known_emails(): void
    {
        Notification::fake();
        $user = $this->customer();

        $known = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nobody-here@gmail.com']);

        $known->assertSessionHasNoErrors()->assertRedirect('/forgot-password');
        $unknown->assertSessionHasNoErrors()->assertRedirect('/forgot-password');
        $this->assertSame(session('status'), $known->getSession()->get('status'));
        $this->assertNotEmpty($unknown->getSession()->get('status'));
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
    }

    // F-20 -------------------------------------------------------------------------------

    public function test_ladder_for_a_negotiated_price_does_not_discount_group_tiers(): void
    {
        $group = CustomerGroup::query()->create(['name' => 'Ladder Group', 'slug' => 'ladder-group', 'discount_bps' => 1000]);
        $company = Company::factory()->approved()->create(['customer_group_id' => $group->id]);
        $product = Product::factory()->create(['base_price_cents' => 2000]);
        PriceTier::create(['product_id' => $product->id, 'customer_group_id' => $group->id, 'min_qty' => 1, 'unit_price_cents' => 1500]);
        PriceTier::create(['product_id' => $product->id, 'customer_group_id' => $group->id, 'min_qty' => 50, 'unit_price_cents' => 1200]);
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $product->id, 'unit_price_cents' => 1000]);

        $result = app(PriceResolver::class)->resolve($product, null, 10, $company->load('customerGroup'));

        $this->assertSame(1000, $result->unitCents, 'negotiated price wins');
        $this->assertSame([1500, 1200], array_column($result->ladder, 'unit_cents'), 'group tiers shown at their real (undiscounted) prices');
    }

    public function test_ladder_still_applies_the_group_discount_to_standard_tiers(): void
    {
        $group = CustomerGroup::query()->create(['name' => 'Std Group', 'slug' => 'std-group', 'discount_bps' => 1000]);
        $company = Company::factory()->approved()->create(['customer_group_id' => $group->id]);
        $product = Product::factory()->create(['base_price_cents' => 2000]);
        PriceTier::create(['product_id' => $product->id, 'min_qty' => 1, 'unit_price_cents' => 2000]);
        CustomerPrice::create(['company_id' => $company->id, 'product_id' => $product->id, 'unit_price_cents' => 1000]);

        $result = app(PriceResolver::class)->resolve($product, null, 10, $company->load('customerGroup'));
        $this->assertSame([1800], array_column($result->ladder, 'unit_cents'));
    }

    // F-25 -------------------------------------------------------------------------------

    public function test_new_staff_password_is_shown_once_and_never_kept_in_component_state(): void
    {
        $this->actingAs($this->staff('super-admin'));
        $c = Livewire::test(StaffManager::class)
            ->set('form.name', 'Rep')->set('form.email', 'rep2@x.test')->set('form.role', 'sales-rep')
            ->call('create')->assertHasNoErrors()
            ->assertSee('Temporary password (shown once)');

        $this->assertArrayNotHasKey('createdPassword', $c->instance()->all(), 'no public property holds the password');
        $this->assertStringNotContainsString('createdPassword', json_encode($c->snapshot));

        $c->call('$refresh')->assertDontSee('Temporary password (shown once)');
    }

    // F-26 -------------------------------------------------------------------------------

    public function test_registration_and_profile_updates_store_lowercase_emails(): void
    {
        $this->post('/register', ['name' => 'Mixed Case', 'email' => 'Mixed.Case@Gmail.com', 'company_name' => 'Case Co', 'country' => 'US',
            'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!'])->assertRedirect();
        $user = User::query()->where('name', 'Mixed Case')->sole();
        $this->assertSame('mixed.case@gmail.com', $user->email);

        $this->actingAs($user)->put('/user/profile-information', ['name' => 'Mixed Case', 'email' => 'New.Address@Gmail.com'])->assertSessionHasNoErrors();
        $this->assertSame('new.address@gmail.com', $user->fresh()->email);
    }

    // F-28 -------------------------------------------------------------------------------

    public function test_first_reference_of_a_year_survives_a_concurrent_first_insert(): void
    {
        // Simulate another request creating the year's counter row right after this request
        // looked for it and found nothing (the race at the first reference of a year).
        $raced = false;
        DB::listen(function ($query) use (&$raced) {
            if (! $raced && str_contains($query->sql, 'reference_sequences') && str_starts_with(strtolower(ltrim($query->sql)), 'select')) {
                $raced = true;
                DB::table('reference_sequences')->insert(['name' => 'Q', 'year' => (int) now()->format('Y'), 'last_value' => 7, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        $reference = app(ReferenceGenerator::class)->next('Q');
        $this->assertStringEndsWith('-000008', $reference);
        $this->assertSame(8, ReferenceSequence::query()->where('name', 'Q')->value('last_value'));
    }
}
