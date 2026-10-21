<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewQuoteRequestAlert extends BaseMail
{
    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New quote request {$this->quote->reference} from {$this->quote->company_name}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-quote-request', with: ['quote' => $this->quote->load('items')]);
    }
}
