<?php

namespace Tests\Feature\Store;

use App\Livewire\Admin\ContactMessages;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sara Khan', 'email' => 'Sara@Shop.test', 'phone' => '+92 300 1234567', 'company' => 'Shop Ltd',
            'subject' => 'Bulk / wholesale order', 'message' => 'We would like 500 card holders with our logo.',
            'form_started' => encrypt(now()->subMinute()->timestamp),
        ], $overrides);
    }

    public function test_page_shows_form_and_official_contact_details(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('Contact us for any questions')
            ->assertSee('(+92) 331-4657496')->assertSee('tel:+923314657496', false)
            ->assertSee('info@leamate.com')->assertSee('Capital Road, Sialkot, Pakistan')
            ->assertSee('name="message"', false)->assertSee('required', false);
    }

    public function test_message_is_stored_emailed_and_confirmed(): void
    {
        Mail::fake();
        $this->post('/contact', $this->form())->assertRedirect(route('contact'));
        $this->get('/contact')->assertSee('your message has been sent')->assertDontSee('name="message"', false);

        $message = ContactMessage::query()->sole();
        $this->assertSame('sara@shop.test', $message->email);
        $this->assertSame('Bulk / wholesale order', $message->subject);
        Mail::assertQueued(ContactMessageReceived::class, fn ($m) => $m->hasTo('info@leamate.com') && $m->hasReplyTo('sara@shop.test'));
    }

    public function test_invalid_input_is_rejected_without_a_success_message(): void
    {
        $this->from('/contact')->post('/contact', $this->form(['name' => '', 'email' => 'not-an-email', 'message' => 'short', 'phone' => 'call me']))
            ->assertRedirect('/contact')->assertSessionHasErrors(['name', 'email', 'message', 'phone']);
        $this->assertDatabaseCount('contact_messages', 0);
        $this->get('/contact')->assertSee('Your message was not sent')->assertDontSee('your message has been sent');
    }

    public function test_bots_are_filtered(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class); // rate limits have their own test
        $this->post('/contact', $this->form(['fax_number' => 'spam']))->assertRedirect(route('contact'));
        $this->post('/contact', $this->form(['form_started' => encrypt(now()->timestamp)]))->assertSessionHasErrors('form');
        $this->post('/contact', $this->form(['form_started' => 'tampered']))->assertSessionHasErrors('form');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_submissions_are_rate_limited(): void
    {
        $this->post('/contact', $this->form());
        $this->post('/contact', $this->form());
        $this->post('/contact', $this->form())->assertStatus(429);
        $this->assertDatabaseCount('contact_messages', 2);
    }

    public function test_contact_is_linked_from_header_and_footer(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'href="'.route('contact').'"')); // ribbon, nav, mobile nav, footer
    }

    public function test_staff_read_messages_in_admin(): void
    {
        $message = ContactMessage::create(['name' => 'Ali', 'email' => 'ali@shop.test', 'message' => 'Do you make reversible belts?']);
        $this->actingAs($this->staff());
        $this->get('/admin/messages')->assertOk()->assertSee('ali@shop.test')->assertSee('New');

        Livewire::test(ContactMessages::class)->call('open', $message->id)->assertSee('Do you make reversible belts?');
        $this->assertNotNull($message->fresh()->read_at);
        Livewire::test(ContactMessages::class)->call('delete', $message->id);
        $this->assertDatabaseCount('contact_messages', 0);

        $this->actingAs($this->customer())->get('/admin/messages')->assertForbidden();
    }
}
