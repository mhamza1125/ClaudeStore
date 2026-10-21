<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class QuoteResponded extends BaseMail
{
    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Quote {$this->quote->reference} was {$this->quote->status->label()} by the customer");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quote-responded', with: ['quote' => $this->quote]);
    }
}
