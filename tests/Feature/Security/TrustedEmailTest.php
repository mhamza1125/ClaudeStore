<?php

namespace Tests\Feature\Security;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use App\Rules\TrustedEmail;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrustedEmailTest extends TestCase
{
    use RefreshDatabase;

    private function passes(string $email): bool
    {
        return Validator::make(['email' => $email], ['email' => ['email:rfc', new TrustedEmail]])->passes();
    }

    public function test_disposable_domains_and_their_subdomains_are_rejected(): void
    {
        $this->assertFalse($this->passes('someone@mailinator.com'));
        $this->assertFalse($this->passes('someone@MAILINATOR.com'));
        $this->assertFalse($this->passes('someone@inbox.mailinator.com'));
        $this->assertFalse($this->passes('someone@guerrillamail.com'));
        $this->assertFalse($this->passes('someone@10minutemail.com'));
        $this->assertFalse($this->passes('someone@localhost'));
    }

    public function test_real_providers_and_business_domains_are_accepted(): void
    {
        $this->assertTrue($this->passes('buyer@gmail.com'));
        $this->assertTrue($this->passes('buyer@outlook.com'));
        $this->assertTrue($this->passes('orders@leamate.com'));
    }

    public function test_extra_blocked_and_allowed_domains_from_config(): void
    {
        config(['store.email.blocked_domains' => ['spammy-shop.com']]);
        TrustedEmail::flushBlocklist();
        $this->assertFalse($this->passes('x@spammy-shop.com'));

        config(['store.email.allowed_domains' => ['spammy-shop.com']]);
        $this->assertTrue($this->passes('x@spammy-shop.com'));
        TrustedEmail::flushBlocklist();
    }

    public function test_registration_rejects_temporary_email(): void
    {
        $this->post('/register', [
            'name' => 'Temp', 'email' => 'temp@yopmail.com', 'company_name' => 'Temp Ltd', 'country' => 'GB',
            'password' => 'Correct-Horse-9', 'password_confirmation' => 'Correct-Horse-9',
        ])->assertSessionHasErrors(['email' => TrustedEmail::MESSAGE]);

        $this->assertDatabaseMissing('users', ['email' => 'temp@yopmail.com']);
    }

    public function test_contact_and_newsletter_reject_temporary_email(): void
    {
        Mail::fake();
        $this->post('/contact', [
            'name' => 'Temp', 'email' => 'temp@mailinator.com', 'message' => 'We would like 500 card holders with our logo.',
            'form_started' => encrypt(now()->subMinute()->timestamp),
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('contact_messages', 0);

        app(Settings::class)->set(['newsletter.enabled' => true]);
        $this->post('/newsletter', ['email' => 'temp@guerrillamail.com'])->assertSessionHasErrors('email', null, 'newsletter');
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_existing_address_is_grandfathered_on_profile_update(): void
    {
        $user = User::factory()->create(['email' => 'old@mailinator.com']);
        $updater = app(UpdateUserProfileInformation::class);

        $updater->update($user, ['name' => 'New Name', 'email' => 'old@mailinator.com']);
        $this->assertSame('New Name', $user->refresh()->name);

        $this->expectException(ValidationException::class);
        $updater->update($user, ['name' => 'New Name', 'email' => 'new@yopmail.com']);
    }
}
