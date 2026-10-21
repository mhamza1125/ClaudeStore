<?php

namespace App\Mail;

use App\Models\Quote;
use App\Models\QuoteActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued, retried mail. Permanent failures are logged and, for quote mail,
 * recorded on the quote's activity timeline so staff can see and resend.
 */
abstract class BaseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function failed(\Throwable $e): void
    {
        Log::error('Mail delivery failed', ['mailable' => static::class, 'error' => $e->getMessage()]);
        $quote = $this->relatedQuote();
        if ($quote) {
            QuoteActivity::create([
                'quote_id' => $quote->id,
                'type' => 'email_failed',
                'body' => class_basename(static::class).' could not be delivered: '.$e->getMessage(),
            ]);
        }
    }

    protected function relatedQuote(): ?Quote
    {
        return property_exists($this, 'quote') ? $this->quote : null;
    }
}
