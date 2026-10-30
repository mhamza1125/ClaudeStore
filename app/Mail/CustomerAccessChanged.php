<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells a customer their account was approved (prices unlocked) or declined. */
class CustomerAccessChanged extends BaseMail
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->user->isApproved() ? 'Your trade account is approved' : 'Your trade account application');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.customer-access', with: ['user' => $this->user]);
    }
}
