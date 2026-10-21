<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(setting('newsletter.enabled'), 404);

        // Honeypot: bots fill hidden fields. Pretend success without storing.
        if (filled($request->input('website_url'))) {
            return back()->with('status', 'Thank you for subscribing.');
        }

        $data = $request->validateWithBag('newsletter', ['email' => ['required', 'email:rfc', 'max:255']]);
        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => strtolower($data['email'])],
            ['ip_address' => $request->ip(), 'unsubscribed_at' => null],
        );

        return back()->with('status', 'Thank you for subscribing.');
    }
}
