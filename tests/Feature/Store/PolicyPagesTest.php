<?php

namespace Tests\Feature\Store;

use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_texts_are_shown_with_current_contact_details(): void
    {
        foreach (['terms' => '1. Quotations and orders', 'privacy' => 'Information we collect', 'shipping' => 'Shipping costs', 'returns' => 'Reporting a problem'] as $slug => $heading) {
            $this->get("/{$slug}")->assertOk()
                ->assertSee('<h2>'.$heading, false)
                ->assertSee('info@leamate.com')
                ->assertDontSee('has not been published')
                ->assertDontSee('{email}')->assertDontSee('{brand}');
        }
        $this->get('/terms')->assertSee('valid for 30 days')->assertSee(url('/shipping'), false);
    }

    public function test_admin_text_replaces_the_standard_text_and_html_is_stripped(): void
    {
        app(Settings::class)->set(['policies.returns' => "## Our returns\nCall {phone}.<script>alert(1)</script>"]);
        $this->get('/returns')->assertSee('<h2>Our returns</h2>', false)->assertSee('(+92) 331-4657496')
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Reporting a problem');
    }
}
