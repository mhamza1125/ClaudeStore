<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteSubmissionRequest;
use App\Models\Quote;
use App\Services\QuoteSubmissionService;

class QuoteSubmissionController extends Controller
{
    public function store(QuoteSubmissionRequest $request, QuoteSubmissionService $service)
    {
        if ($request->isSpam()) {
            // Honeypot triggered: respond as if successful, store nothing.
            return redirect()->route('home')->with('status', 'Thank you. Your request has been received.');
        }

        $quote = $service->submit(
            $request->safe()->except(['attachments', 'form_started']),
            $request->file('attachments', []),
            $request->user(),
            $request->ip(),
        );

        $request->session()->put('quote_submitted', $quote->reference);

        return redirect()->route('quote.confirmation');
    }

    public function confirmation()
    {
        $reference = session('quote_submitted');
        abort_unless($reference, 404);
        $quote = Quote::query()->where('reference', $reference)->withCount('items')->firstOrFail();

        return view('store.quote-confirmation', compact('quote'));
    }
}
