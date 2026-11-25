<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('store.contact', ['subjects' => ContactRequest::SUBJECTS]);
    }

    /**
     * The message is saved before anything else, so success is only reported once it is
     * stored (Admin → Messages). Email to the team is queued; a mail outage can't lose it.
     */
    public function store(ContactRequest $request)
    {
        if ($request->isSpam()) {
            // Honeypot filled: answer like a success, store nothing.
            return redirect()->route('contact')->with('contact_sent', true);
        }

        $message = ContactMessage::create([
            ...$request->safe()->only(['name', 'phone', 'company', 'subject', 'message']),
            'email' => strtolower($request->validated('email')),
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
        ]);

        $to = setting('sales.notification_email') ?: setting('brand.email');
        try {
            Mail::to($to)->queue(new ContactMessageReceived($message));
        } catch (\Throwable $e) {
            Log::error('Contact message notification could not be queued', ['message' => $message->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('contact')->with('contact_sent', true);
    }
}
