<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WholesaleStatusChanged extends BaseMail
{
    public function __construct(public Company $company) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your trade account application: '.($this->company->wholesale_status === 'approved' ? 'approved' : 'update'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.wholesale-status', with: ['company' => $this->company]);
    }
}
