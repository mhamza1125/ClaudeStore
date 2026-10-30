<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sales alert for a new customer registration (B2B lead), sent while B2B pricing is on. */
class NewCustomerRegistered extends BaseMail
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New trade account'.($this->user->approval_status === 'pending' ? ' awaiting approval' : '').': '.($this->user->company?->name ?? $this->user->name));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-customer', with: ['user' => $this->user->loadMissing('company')]);
    }
}
