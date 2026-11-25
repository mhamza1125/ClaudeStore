<?php

namespace App\Mail;

use App\Models\Quote;
use App\Models\QuoteVersion;
use App\Quotes\QuotationDocument;
use App\Services\QuotePdf;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The quotation email. It carries the sent QuoteVersion, so a worker that runs late renders
 * that version's snapshot, never figures staff edited afterwards (audit F-02).
 */
class QuotationSent extends BaseMail
{
    /** @param string $link secure customer link containing a one-off token */
    public function __construct(public Quote $quote, public QuoteVersion $version, public string $link) {}

    public function document(): QuotationDocument
    {
        return QuotationDocument::fromVersion($this->quote, $this->version);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your quotation {$this->quote->reference} from ".setting('brand.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quotation-sent', with: ['doc' => $this->document(), 'link' => $this->link]);
    }

    public function attachments(): array
    {
        $doc = $this->document();

        return [
            Attachment::fromData(fn () => app(QuotePdf::class)->render($doc), "{$doc->reference}-v{$doc->version}.pdf")->withMime('application/pdf'),
        ];
    }
}
