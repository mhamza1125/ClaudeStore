<?php

namespace Tests\Feature\B2b;

use App\Livewire\Admin\B2bSettings;
use App\Livewire\Admin\CustomerAccounts;
use App\Mail\CustomerAccessChanged;
use App\Mail\NewCustomerRegistered;
use App\Models\Product;
use App\Models\User;
use App\Notifications\VerifyEmailQueued;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/** Registration, email verification, approval and the admin B2B screens. */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function b2b(array $settings = []): void
    {
        app(Settings::class)->set(array_merge(['b2b.enabled' => true, 'b2b.require_registration' => true], $settings));
    }

    private function register(string $email = 'buyer@shop.test')
    {
        return $this->post('/register', [
            'name' => 'Bea Buyer', 'email' => $email, 'company_name' => 'Shop Ltd', 'country' => 'GB',
            'password' => 'Correct-Horse-9', 'password_confirmation' => 'Correct-Horse-9',
        ]);
    }

    public function test_register_and_access_grants_prices_immediately(): void
    {
        Mail::fake();
        Notification::fake();
        $this->b2b();
        $product = Product::factory()->create(['base_price_cents' => 4200]);

        $this->register()->assertRedirect('/account');
        $user = User::query()->where('email', 'buyer@shop.test')->sole();
        $this->assertSame('approved', $user->approval_status);
        Notification::assertSentTo($user, VerifyEmailQueued::class);
        Mail::assertQueued(NewCustomerRegistered::class);

        $this->get(route('products.show', $product))->assertSee('$42.00');
    }

    public function test_registration_waits_for_approval_when_required(): void
    {
        Mail::fake();
        $this->b2b(['b2b.require_approval' => true]);
        $this->get('/register')->assertSee('you’ll see prices once yours is approved', false);

        $this->register()->assertRedirect('/account');
        $user = User::query()->where('email', 'buyer@shop.test')->sole();
        $this->assertSame('pending', $user->approval_status);
        $this->assertNull($user->approved_at);
        Mail::assertQueued(NewCustomerRegistered::class, fn ($m) => str_contains($m->envelope()->subject, 'awaiting approval'));
        $this->get('/account')->assertSee('Prices after approval');
    }

    public function test_no_approval_is_needed_unless_enabled(): void
    {
        Mail::fake();
        $this->register(); // default settings: B2B on, registration required, no approval
        $this->assertSame('approved', User::query()->where('email', 'buyer@shop.test')->value('approval_status'));
        Mail::assertQueued(NewCustomerRegistered::class, fn ($m) => ! str_contains($m->envelope()->subject, 'awaiting approval'));
    }

    public function test_no_lead_alert_while_b2b_pricing_is_off(): void
    {
        Mail::fake();
        app(Settings::class)->set(['b2b.enabled' => false]);
        $this->register();
        Mail::assertNotQueued(NewCustomerRegistered::class);
    }

    public function test_signed_verification_link_verifies_the_email(): void
    {
        $this->b2b(['b2b.require_email_verification' => true]);
        $user = User::factory()->unverified()->create()->assignRole('customer');
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        // Tampered or other users' links are refused.
        $this->actingAs($user)->get($url.'x')->assertForbidden();
        $other = User::factory()->unverified()->create();
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        $this->actingAs($user)->get($url)->assertRedirect('/account?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get('/account?verified=1')->assertSee('your email address is verified');
    }

    public function test_expired_verification_link_is_refused(): void
    {
        $user = User::factory()->unverified()->create()->assignRole('customer');
        $url = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create()->assignRole('customer');
        $this->actingAs($user)->get('/email/verify')->assertOk()->assertSee('Resend verification email');
        $this->get('/account/profile')->assertSee('Email not verified');
        $this->post('/email/verification-notification')->assertRedirect()->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmailQueued::class);
    }

    public function test_changing_email_requires_verifying_the_new_address(): void
    {
        Notification::fake();
        $user = $this->customer();
        $this->actingAs($user)->put('/user/profile-information', ['name' => $user->name, 'email' => 'new@shop.test'])->assertRedirect();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($user->fresh(), VerifyEmailQueued::class);
    }

    public function test_admin_approves_and_rejects_customers(): void
    {
        Mail::fake();
        $this->b2b(['b2b.require_approval' => true]);
        $pending = User::factory()->pendingApproval()->create(['email' => 'p@shop.test'])->assignRole('customer');
        $admin = $this->staff();
        $this->actingAs($admin);

        $this->get('/admin')->assertSee('data-testid="pending-accounts"', false);
        $this->get('/admin/customer-accounts?status=pending')->assertOk()->assertSee('p@shop.test')->assertSee('Needs approval');

        Livewire::test(CustomerAccounts::class)->call('approve', $pending->id);
        $pending->refresh();
        $this->assertSame('approved', $pending->approval_status);
        $this->assertSame($admin->id, $pending->approved_by);
        Mail::assertQueued(CustomerAccessChanged::class, fn ($m) => $m->hasTo('p@shop.test'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.approved', 'subject_id' => $pending->id]);

        Livewire::test(CustomerAccounts::class)->call('reject', $pending->id);
        $this->assertSame('rejected', $pending->fresh()->approval_status);
    }

    public function test_admin_manages_verification_overrides_and_suspension(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create()->assignRole('customer');
        $this->actingAs($this->staff());

        Livewire::test(CustomerAccounts::class)->call('resendVerification', $user->id);
        Notification::assertSentTo($user, VerifyEmailQueued::class);
        Livewire::test(CustomerAccounts::class)->call('markVerified', $user->id);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        Livewire::test(CustomerAccounts::class)->call('setPriceAccess', $user->id, 'deny');
        $this->assertSame('deny', $user->fresh()->price_access);
        Livewire::test(CustomerAccounts::class)->call('setPriceAccess', $user->id, 'bogus')->assertStatus(422);

        Livewire::test(CustomerAccounts::class)->call('toggleActive', $user->id);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.suspended']);
    }

    public function test_staff_accounts_cannot_be_changed_from_customer_accounts(): void
    {
        $staff = $this->staff();
        $this->actingAs($this->staff());
        try {
            Livewire::test(CustomerAccounts::class)->call('toggleActive', $staff->id);
            $this->fail('A staff account was looked up as a customer.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        }
        $this->assertNotFalse($staff->fresh()->is_active);
    }

    public function test_sales_reps_can_view_but_not_change_customer_access(): void
    {
        $user = User::factory()->pendingApproval()->create()->assignRole('customer');
        $this->actingAs($this->staff('sales-rep'));
        $this->get('/admin/customer-accounts')->assertOk()->assertDontSee('wire:click="approve', false);
        Livewire::test(CustomerAccounts::class)->call('approve', $user->id)->assertForbidden();
        $this->assertSame('pending', $user->fresh()->approval_status);
        $this->get('/admin/b2b-settings')->assertForbidden();
    }

    public function test_b2b_settings_are_saved_to_the_database_and_audited(): void
    {
        $this->actingAs($this->staff());
        $this->get('/admin/b2b-settings')->assertOk()->assertSee('Register and access');

        Livewire::test(B2bSettings::class)
            ->set('s.enabled', true)->set('s.require_email_verification', true)->set('s.require_approval', true)
            ->set('s.guest_inquiries', false)->set('s.watermark_personalized', true)->set('s.watermark_identifier', 'id')
            ->set('s.watermark_opacity', 30)->set('s.watermark_text', '  Atelier Trade  ')
            ->assertSee('Register, verify and wait for approval')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'b2b.enabled', 'value' => 'true']);
        $this->assertDatabaseHas('settings', ['key' => 'b2b.guest_inquiries', 'value' => 'false']);
        $this->assertDatabaseHas('settings', ['key' => 'images.watermark_text', 'value' => '"Atelier Trade"']);
        $this->assertSame(30, setting('images.watermark_opacity'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.b2b_updated']);

        Livewire::test(B2bSettings::class)->set('s.watermark_opacity', 90)->set('s.watermark_identifier', 'phone')
            ->call('save')->assertHasErrors(['s.watermark_opacity', 's.watermark_identifier']);
    }
}
