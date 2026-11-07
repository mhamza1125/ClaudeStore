<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityBadgesTest extends TestCase
{
    use RefreshDatabase;

    private function message(): ContactMessage
    {
        return ContactMessage::create(['name' => 'Sara', 'email' => 'sara@shop.test', 'message' => 'Hello there, we need a quote.']);
    }

    public function test_new_customers_and_messages_show_badges_until_seen(): void
    {
        $admin = $this->staff();
        $this->travel(1)->minutes();
        $this->customer();
        $this->customer();
        Company::factory()->create();
        $this->message();

        $counts = $this->actingAs($admin)->getJson('/admin/activity')->assertOk()->json('counts');
        $this->assertSame(2, $counts['admin.customer-accounts']);
        $this->assertSame(1, $counts['admin.companies.index']);
        $this->assertSame(1, $counts['admin.messages']);
        $this->assertSame(0, $counts['admin.quotes.index']);

        $this->get('/admin')->assertOk()->assertSee('data-badge="admin.customer-accounts"', false)
            ->assertSee('2 new customer accounts');

        // Opening the section clears its badge for this admin only.
        $this->get('/admin/customer-accounts')->assertOk();
        $this->assertSame(0, $this->getJson('/admin/activity')->json('counts')['admin.customer-accounts']);
        $this->assertSame(1, $this->getJson('/admin/activity')->json('counts')['admin.companies.index']);

        $other = $this->staff();
        $this->assertSame(0, $this->actingAs($other)->getJson('/admin/activity')->json('counts')['admin.customer-accounts'],
            'a newly created admin only sees activity from after they joined');

        // Activity after the visit shows up again.
        $this->travel(1)->minutes();
        $this->customer();
        $this->assertSame(1, $this->actingAs($admin)->getJson('/admin/activity')->json('counts')['admin.customer-accounts']);
    }

    public function test_messages_badge_counts_unread_messages(): void
    {
        $admin = $this->staff();
        $m = $this->message();
        $this->message();
        $this->actingAs($admin)->get('/admin/messages')->assertOk();
        $this->assertSame(2, $this->getJson('/admin/activity')->json('counts')['admin.messages'], 'visiting does not mark messages read');

        $m->update(['read_at' => now()]);
        $this->assertSame(1, $this->getJson('/admin/activity')->json('counts')['admin.messages']);
    }

    public function test_badges_respect_permissions(): void
    {
        $counts = $this->actingAs($this->staff('sales-rep'))->getJson('/admin/activity')->assertOk()->json('counts');
        $this->assertArrayHasKey('admin.quotes.index', $counts);
        $this->assertArrayNotHasKey('admin.orders.index', $counts);

        $this->actingAs(User::factory()->create()->assignRole('customer'))->getJson('/admin/activity')->assertForbidden();
    }
}
