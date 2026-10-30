<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Contact Us submission for the team; replying answers the sender directly. */
class ContactMessageReceived extends BaseMail
{
    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Website message: '.($this->contactMessage->subject ?: 'General inquiry').' — '.$this->contactMessage->name,
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-message', with: ['m' => $this->contactMessage]);
    }
}
