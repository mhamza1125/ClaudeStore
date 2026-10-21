<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WholesaleApplicationReceived extends BaseMail
{
    public function __construct(public Company $company) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Wholesale access requested: {$this->company->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.wholesale-application', with: ['company' => $this->company]);
    }
}
