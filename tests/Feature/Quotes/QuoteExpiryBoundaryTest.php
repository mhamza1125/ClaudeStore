<?php

namespace Tests\Feature\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Valid until D" means valid through the end of D in business time (Asia/Karachi, owner
 * decision D7). Audit F-13.
 */
class QuoteExpiryBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private function sent(string $validUntil): Quote
    {
        return Quote::factory()->create(['status' => QuoteStatus::AwaitingResponse, 'version' => 1,
            'valid_until' => $validUntil, 'access_token_hash' => hash('sha256', 't')]);
    }

    public function test_quote_is_answerable_on_its_last_business_day(): void
    {
        // 18:00 UTC on 10 March is 23:00 on 10 March in Karachi: still the last valid day.
        $this->travelTo(Carbon::parse('2026-03-10 18:00:00', 'UTC'));
        $quote = $this->sent('2026-03-10');

        $this->assertFalse($quote->isExpired());
        $this->assertTrue($quote->canBeAnsweredByCustomer());
    }

    public function test_quote_expires_when_the_business_day_ends(): void
    {
        // 20:00 UTC on 10 March is 01:00 on 11 March in Karachi: the quotation has expired.
        $this->travelTo(Carbon::parse('2026-03-10 20:00:00', 'UTC'));
        $quote = $this->sent('2026-03-10');

        $this->assertTrue($quote->isExpired(), 'expired after the end of the business day');
        $this->assertFalse($quote->canBeAnsweredByCustomer());
        $this->artisan('quotes:expire')->assertSuccessful();
        $this->assertSame(QuoteStatus::Expired, $quote->fresh()->status, 'the scheduled job expires it on the business calendar');
    }

    public function test_scheduled_job_is_not_early_on_the_last_business_day(): void
    {
        // 18:00 UTC on 10 March is 23:00 on 10 March in Karachi: not yet expired.
        $this->travelTo(Carbon::parse('2026-03-10 18:00:00', 'UTC'));
        $quote = $this->sent('2026-03-10');
        $this->artisan('quotes:expire')->assertSuccessful();
        $this->assertSame(QuoteStatus::AwaitingResponse, $quote->fresh()->status);
    }

    public function test_expiry_job_runs_at_00_15_business_time(): void
    {
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'quotes:expire'));

        $this->assertNotNull($event);
        $this->assertSame('15 0 * * *', $event->expression);
        $this->assertSame('Asia/Karachi', (string) $event->timezone);
    }

    public function test_app_timezone_is_unchanged(): void
    {
        // Owner decision D7: stored timestamps stay UTC; only validity uses the business time zone.
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('Asia/Karachi', config('store.business_timezone'));
    }
}
