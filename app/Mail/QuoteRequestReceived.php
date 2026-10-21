<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class QuoteRequestReceived extends BaseMail
{
    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "We received your quote request {$this->quote->reference}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quote-request-received', with: ['quote' => $this->quote->load('items')]);
    }
}
