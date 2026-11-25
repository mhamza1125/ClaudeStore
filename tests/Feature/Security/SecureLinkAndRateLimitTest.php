<?php

namespace Tests\Feature\Security;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Characterization (audit §6, §10.5): the secure-link limiter and the newsletter limiter.
 * The other limiters already have tests: login (AuthorizationTest), quote submission
 * (QuoteSubmissionTest), contact (ContactPageTest), search (SearchSuggestTest) and the
 * chatbot (ChatbotTest).
 */
class SecureLinkAndRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function sentQuote(): Quote
    {
        return Quote::factory()->create(['status' => QuoteStatus::AwaitingResponse, 'version' => 1,
            'valid_until' => now()->addDays(10), 'access_token_hash' => hash('sha256', 'right-token')]);
    }

    public function test_wrong_token_and_unknown_reference_give_the_same_404(): void
    {
        $quote = $this->sentQuote();

        $wrongToken = $this->get("/q/{$quote->reference}/wrong-token");
        $unknownRef = $this->get('/q/LM-Q-2026-999999/wrong-token');

        $wrongToken->assertNotFound();
        $unknownRef->assertNotFound();
        $this->assertSame($unknownRef->getContent(), $wrongToken->getContent(), 'The two 404 responses must be indistinguishable.');
    }

    public function test_secure_link_views_are_rate_limited_per_ip(): void
    {
        $quote = $this->sentQuote();
        foreach (range(1, 10) as $i) {
            $this->get("/q/{$quote->reference}/guess-{$i}")->assertNotFound();
        }
        $this->get("/q/{$quote->reference}/right-token")->assertStatus(429);
    }

    public function test_secure_link_responses_count_towards_the_same_limit(): void
    {
        $quote = $this->sentQuote();
        foreach (range(1, 10) as $i) {
            $this->post("/q/{$quote->reference}/guess-{$i}/accept", ['version' => 1, 'name' => 'x', 'confirm' => 1]);
        }
        $this->post("/q/{$quote->reference}/right-token/accept", ['version' => 1, 'name' => 'x', 'confirm' => 1])->assertStatus(429);
        $this->assertSame(QuoteStatus::AwaitingResponse, $quote->fresh()->status);
    }

    public function test_newsletter_signups_are_rate_limited(): void
    {
        app(Settings::class)->set(['newsletter.enabled' => true]);
        foreach (range(1, 5) as $i) {
            $this->post('/newsletter', ['email' => "buyer{$i}@gmail.com"]);
        }
        $this->post('/newsletter', ['email' => 'buyer6@gmail.com'])->assertStatus(429);
    }
}
