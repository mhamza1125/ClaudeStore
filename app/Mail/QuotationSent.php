<?php

namespace App\Mail;

use App\Models\Quote;
use App\Services\QuotePdf;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class QuotationSent extends BaseMail
{
    /** @param string $link secure customer link containing a one-off token */
    public function __construct(public Quote $quote, public string $link) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your quotation {$this->quote->reference} from ".setting('brand.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quotation-sent', with: ['quote' => $this->quote->load('items'), 'link' => $this->link]);
    }

    public function attachments(): array
    {
        $quote = $this->quote;

        return [
            Attachment::fromData(fn () => app(QuotePdf::class)->render($quote), "{$quote->reference}.pdf")->withMime('application/pdf'),
        ];
    }
}
